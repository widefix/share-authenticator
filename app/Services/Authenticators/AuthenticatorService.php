<?php

namespace App\Services\Authenticators;

use App\Data\AuthenticatorCode;
use App\Data\AuthenticatorData;
use App\Enums\AuditAction;
use App\Models\Authenticator;
use App\Models\User;
use App\Models\Vault;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class AuthenticatorService
{
    public function __construct(
        private AuthenticatorCipher $cipher,
        private TotpService $totp,
        private AuditLogger $audit,
    ) {}

    public function create(
        User $actor,
        Vault $vault,
        #[\SensitiveParameter] AuthenticatorData $data,
        bool $confirmDuplicate = false,
    ): Authenticator {
        return DB::transaction(function () use ($actor, $vault, $data, $confirmDuplicate): Authenticator {
            $vault = Vault::query()->lockForUpdate()->findOrFail($vault->getKey());
            $this->authorizeVault($actor, $vault);
            $this->ensureActiveVault($vault);
            $this->ensureNotDuplicate($vault, $data, $confirmDuplicate);

            $uuid = (string) Str::uuid();
            $authenticator = Authenticator::query()->create([
                'uuid' => $uuid,
                'vault_id' => $vault->getKey(),
                ...$this->cipher->encrypt($vault, $uuid, $data),
            ]);
            $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorCreated, $authenticator);

            return $authenticator;
        });
    }

    public function update(
        User $actor,
        Authenticator $authenticator,
        Vault $targetVault,
        string $issuer,
        string $account,
        #[\SensitiveParameter] ?AuthenticatorData $replacement = null,
        bool $confirmSensitiveChange = false,
        bool $confirmDuplicate = false,
    ): Authenticator {
        return DB::transaction(function () use ($actor, $authenticator, $targetVault, $issuer, $account, $replacement, $confirmSensitiveChange, $confirmDuplicate): Authenticator {
            $authenticator = Authenticator::query()->lockForUpdate()->findOrFail($authenticator->getKey());
            $targetVault = Vault::query()->lockForUpdate()->findOrFail($targetVault->getKey());
            $this->authorize($actor, $authenticator);
            $this->authorizeVault($actor, $targetVault);
            $this->ensureActiveVault($targetVault);

            $current = $this->cipher->decrypt($authenticator);
            $moved = $authenticator->vault_id !== $targetVault->getKey();
            $replaced = $replacement !== null;
            if ($replaced && ! $confirmSensitiveChange) {
                throw ValidationException::withMessages([
                    'confirm_sensitive_change' => 'Confirm that replacing the secret or TOTP settings will invalidate the previous codes.',
                ]);
            }

            $data = $replacement
                ? new AuthenticatorData($issuer, $account, $replacement->secret, $replacement->algorithm, $replacement->digits, $replacement->period)
                : new AuthenticatorData($issuer, $account, $current->secret, $current->algorithm, $current->digits, $current->period);

            $this->ensureNotDuplicate($targetVault, $data, $confirmDuplicate, $authenticator->getKey());
            $authenticator->forceFill([
                'vault_id' => $targetVault->getKey(),
                ...$this->cipher->encrypt($targetVault, $authenticator->uuid, $data),
            ])->save();
            $authenticator->unsetRelation('vault')->load('vault');

            $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorUpdated, $authenticator);
            if ($moved) {
                $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorMoved, $authenticator);
            }
            if ($replaced) {
                $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorSecretReplaced, $authenticator);
            }

            return $authenticator;
        });
    }

    public function archive(User $actor, Authenticator $authenticator): Authenticator
    {
        return DB::transaction(function () use ($actor, $authenticator): Authenticator {
            $authenticator = Authenticator::query()->lockForUpdate()->findOrFail($authenticator->getKey());
            $this->authorize($actor, $authenticator);
            if (! $authenticator->isArchived()) {
                $authenticator->update(['archived_at' => now()]);
                $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorArchived, $authenticator);
            }

            return $authenticator;
        });
    }

    public function restore(User $actor, Authenticator $authenticator): Authenticator
    {
        return DB::transaction(function () use ($actor, $authenticator): Authenticator {
            $authenticator = Authenticator::query()->lockForUpdate()->findOrFail($authenticator->getKey());
            $this->authorize($actor, $authenticator);
            $this->ensureActiveVault($authenticator->vault);
            if ($authenticator->isArchived()) {
                $authenticator->update(['archived_at' => null]);
                $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorRestored, $authenticator);
            }

            return $authenticator;
        });
    }

    public function delete(User $actor, Authenticator $authenticator, string $confirmation): void
    {
        DB::transaction(function () use ($actor, $authenticator, $confirmation): void {
            $authenticator = Authenticator::query()->lockForUpdate()->findOrFail($authenticator->getKey());
            $this->authorize($actor, $authenticator);
            if (! $authenticator->isArchived()) {
                throw ValidationException::withMessages(['authenticator' => 'Archive this authenticator before deleting it.']);
            }

            $data = $this->cipher->decrypt($authenticator);
            if (! hash_equals($data->label(), trim($confirmation))) {
                throw ValidationException::withMessages(['confirmation' => 'Enter the authenticator label exactly to confirm deletion.']);
            }

            $this->audit->record($actor->workspace, $actor, AuditAction::AuthenticatorDeleted, $authenticator);
            $authenticator->delete();
        });
    }

    public function code(User $actor, Authenticator $authenticator, AuditAction $action): AuthenticatorCode
    {
        $this->authorizeAccess($actor, $authenticator);
        if ($authenticator->isArchived() || $authenticator->vault->isArchived()) {
            throw new AuthorizationException;
        }

        $code = $this->totp->generate($this->cipher->decrypt($authenticator));
        $this->audit->record($actor->workspace, $actor, $action, $authenticator);

        return $code;
    }

    private function ensureNotDuplicate(
        Vault $vault,
        #[\SensitiveParameter] AuthenticatorData $data,
        bool $confirmed,
        ?int $exceptId = null,
    ): void {
        $fingerprint = $this->cipher->fingerprint($vault, $data);
        $exists = Authenticator::query()
            ->join('vaults', 'vaults.id', '=', 'authenticators.vault_id')
            ->where('vaults.workspace_id', $vault->workspace_id)
            ->where('authenticators.fingerprint', $fingerprint)
            ->when($exceptId, fn ($query) => $query->where('authenticators.id', '!=', $exceptId))
            ->exists();

        if ($exists && ! $confirmed) {
            throw ValidationException::withMessages([
                'duplicate' => 'This TOTP secret and configuration already exist in the workspace. Confirm to save another copy.',
            ]);
        }
    }

    private function authorize(User $actor, Authenticator $authenticator): void
    {
        $this->authorizeVault($actor, $authenticator->vault);
    }

    private function authorizeAccess(User $actor, Authenticator $authenticator): void
    {
        if (! $actor->canAccessVault($authenticator->vault)) {
            throw new AuthorizationException;
        }
    }

    private function authorizeVault(User $actor, Vault $vault): void
    {
        if (! $actor->isWorkspaceAdmin() || $actor->workspace_id !== $vault->workspace_id) {
            throw new AuthorizationException;
        }
    }

    private function ensureActiveVault(Vault $vault): void
    {
        if ($vault->isArchived()) {
            throw ValidationException::withMessages(['vault_id' => 'Choose an active vault.']);
        }
    }
}

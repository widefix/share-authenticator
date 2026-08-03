<?php

namespace App\Services\Vaults;

use App\Data\DeletedVault;
use App\Enums\AuditAction;
use App\Models\User;
use App\Models\Vault;
use App\Services\Audit\AuditLogger;
use App\Services\Shared\NameNormalizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class VaultService
{
    public function __construct(
        private NameNormalizer $names,
        private AuditLogger $audit,
    ) {}

    public function create(User $actor, string $name): Vault
    {
        $this->authorizeWorkspaceAdmin($actor);

        return DB::transaction(function () use ($actor, $name): Vault {
            $displayName = $this->names->display($name);
            $normalizedName = $this->names->normalize($name);

            $this->ensureNameIsAvailable($actor->workspace_id, $normalizedName);

            try {
                $vault = Vault::query()->create([
                    'workspace_id' => $actor->workspace_id,
                    'name' => $displayName,
                    'normalized_name' => $normalizedName,
                ]);
            } catch (UniqueConstraintViolationException) {
                $this->throwDuplicateName();
            }

            $this->audit->record(
                $actor->workspace,
                $actor,
                AuditAction::VaultCreated,
                $vault,
            );

            return $vault;
        });
    }

    public function rename(User $actor, Vault $vault, string $name): Vault
    {
        return DB::transaction(function () use ($actor, $vault, $name): Vault {
            $vault = Vault::query()->lockForUpdate()->findOrFail($vault->getKey());
            $this->authorizeVault($actor, $vault);

            $displayName = $this->names->display($name);
            $normalizedName = $this->names->normalize($name);

            if ($vault->normalized_name === $normalizedName && $vault->name === $displayName) {
                return $vault;
            }

            $this->ensureNameIsAvailable($vault->workspace_id, $normalizedName, $vault->getKey());

            try {
                $vault->update([
                    'name' => $displayName,
                    'normalized_name' => $normalizedName,
                ]);
            } catch (UniqueConstraintViolationException) {
                $this->throwDuplicateName();
            }

            $this->audit->record(
                $actor->workspace,
                $actor,
                AuditAction::VaultRenamed,
                $vault,
            );

            return $vault;
        });
    }

    public function archive(User $actor, Vault $vault): Vault
    {
        return DB::transaction(function () use ($actor, $vault): Vault {
            $vault = Vault::query()->lockForUpdate()->findOrFail($vault->getKey());
            $this->authorizeVault($actor, $vault);

            if ($vault->isArchived()) {
                return $vault;
            }

            $this->ensureAnotherActiveVaultExists($vault);
            $vault->update(['archived_at' => now()]);
            $this->audit->record(
                $actor->workspace,
                $actor,
                AuditAction::VaultArchived,
                $vault,
            );

            return $vault;
        });
    }

    public function restore(User $actor, Vault $vault): Vault
    {
        return DB::transaction(function () use ($actor, $vault): Vault {
            $vault = Vault::query()->lockForUpdate()->findOrFail($vault->getKey());
            $this->authorizeVault($actor, $vault);

            if (! $vault->isArchived()) {
                return $vault;
            }

            $vault->update(['archived_at' => null]);
            $this->audit->record(
                $actor->workspace,
                $actor,
                AuditAction::VaultRestored,
                $vault,
            );

            return $vault;
        });
    }

    public function delete(User $actor, Vault $vault, string $confirmation): DeletedVault
    {
        return DB::transaction(function () use ($actor, $vault, $confirmation): DeletedVault {
            $vault = Vault::query()->lockForUpdate()->findOrFail($vault->getKey());
            $this->authorizeVault($actor, $vault);

            if (! hash_equals($vault->name, $this->names->display($confirmation))) {
                throw ValidationException::withMessages([
                    'confirmation' => 'Enter the vault name exactly to confirm deletion.',
                ]);
            }

            if (! $vault->isArchived()) {
                $this->ensureAnotherActiveVaultExists($vault);
            }

            $summary = new DeletedVault(
                authenticatorCount: 0,
                memberCount: $actor->workspace->users()->count(),
            );

            $this->audit->record(
                $actor->workspace,
                $actor,
                AuditAction::VaultDeleted,
                $vault,
            );
            $vault->delete();

            return $summary;
        });
    }

    private function authorizeWorkspaceAdmin(User $actor): void
    {
        if (! $actor->workspace_id || ! $actor->isWorkspaceAdmin()) {
            throw new AuthorizationException;
        }
    }

    private function authorizeVault(User $actor, Vault $vault): void
    {
        $this->authorizeWorkspaceAdmin($actor);

        if ($actor->workspace_id !== $vault->workspace_id) {
            throw new AuthorizationException;
        }
    }

    private function ensureNameIsAvailable(
        int $workspaceId,
        string $normalizedName,
        ?int $exceptVaultId = null,
    ): void {
        $exists = Vault::query()
            ->where('workspace_id', $workspaceId)
            ->where('normalized_name', $normalizedName)
            ->when($exceptVaultId, fn ($query) => $query->whereKeyNot($exceptVaultId))
            ->exists();

        if ($exists) {
            $this->throwDuplicateName();
        }
    }

    private function ensureAnotherActiveVaultExists(Vault $vault): void
    {
        $activeVaults = Vault::query()
            ->where('workspace_id', $vault->workspace_id)
            ->active()
            ->lockForUpdate()
            ->count();

        if ($activeVaults <= 1) {
            throw ValidationException::withMessages([
                'vault' => 'A workspace must always have at least one active vault.',
            ]);
        }
    }

    private function throwDuplicateName(): never
    {
        throw ValidationException::withMessages([
            'name' => 'A vault with this name already exists in the workspace.',
        ]);
    }
}

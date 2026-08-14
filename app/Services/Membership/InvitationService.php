<?php

namespace App\Services\Membership;

use App\Data\IssuedWorkspaceInvitation;
use App\Enums\AuditAction;
use App\Enums\RotationReason;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Vault;
use App\Models\WorkspaceInvitation;
use App\Services\Audit\AuditLogger;
use App\Services\Rotation\RotationTaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InvitationService
{
    public const EXPIRATION_DAYS = 7;

    public function __construct(
        private AuditLogger $audit,
        private RotationTaskService $rotationTasks,
    ) {}

    /** @param array<int, int> $vaultIds */
    public function invite(User $actor, string $email, WorkspaceRole $role, array $vaultIds): IssuedWorkspaceInvitation
    {
        $this->authorizeAdmin($actor);
        $email = Str::lower(trim($email));
        $vaultIds = $this->validatedVaultIds($actor, $role, $vaultIds);
        $token = bin2hex(random_bytes(32));

        $invitation = DB::transaction(function () use ($actor, $email, $role, $vaultIds, $token): WorkspaceInvitation {
            if (User::query()->where('email', $email)->where('workspace_id', $actor->workspace_id)->exists()) {
                throw ValidationException::withMessages(['email' => 'This person is already a member of the workspace.']);
            }

            WorkspaceInvitation::query()
                ->where('workspace_id', $actor->workspace_id)
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->whereNull('expired_at')
                ->lockForUpdate()
                ->get()
                ->each(function (WorkspaceInvitation $existing) use ($actor): void {
                    if ($existing->expires_at->isPast()) {
                        $existing->update(['expired_at' => now()]);
                        $this->audit->record($actor->workspace, $actor, AuditAction::InvitationExpired, $existing);
                    } else {
                        $existing->update(['revoked_at' => now()]);
                        $this->audit->record($actor->workspace, $actor, AuditAction::InvitationRevoked, $existing);
                    }
                });

            $invitation = WorkspaceInvitation::query()->create([
                'uuid' => (string) Str::uuid(),
                'workspace_id' => $actor->workspace_id,
                'invited_by_user_id' => $actor->getKey(),
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::EXPIRATION_DAYS),
            ]);
            $invitation->vaults()->sync($vaultIds);
            $this->audit->record($actor->workspace, $actor, AuditAction::InvitationCreated, $invitation);

            return $invitation;
        });

        return new IssuedWorkspaceInvitation($invitation, $token);
    }

    public function accept(User $user, WorkspaceInvitation $invitation, #[\SensitiveParameter] string $token): void
    {
        $accepted = DB::transaction(function () use ($user, $invitation, $token): bool {
            $invitation = WorkspaceInvitation::query()->lockForUpdate()->findOrFail($invitation->getKey());
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! hash_equals($invitation->token_hash, hash('sha256', $token))) {
                throw new AuthorizationException;
            }

            if ($invitation->accepted_at || $invitation->revoked_at || $invitation->expired_at) {
                throw new AuthorizationException;
            }

            if ($invitation->expires_at->isPast()) {
                $invitation->update(['expired_at' => now()]);
                $this->audit->record($invitation->workspace, $user, AuditAction::InvitationExpired, $invitation);

                return false;
            }

            if (! hash_equals($invitation->email, Str::lower(trim($user->email)))) {
                throw new AuthorizationException;
            }

            if ($user->workspace_id === $invitation->workspace_id) {
                throw new AuthorizationException;
            }

            if ($user->workspace_id !== null) {
                $this->leaveCurrentWorkspace($user);
            }

            $user->forceFill([
                'workspace_id' => $invitation->workspace_id,
                'role' => $invitation->role,
            ])->save();

            $user->vaults()->syncWithPivotValues(
                $invitation->role === WorkspaceRole::Member ? $invitation->vaults()->pluck('vaults.id')->all() : [],
                ['granted_by_user_id' => $invitation->invited_by_user_id],
            );
            if ($invitation->role === WorkspaceRole::Member) {
                $invitation->vaults()->each(
                    fn (Vault $vault) => $this->audit->record($invitation->workspace, $user, AuditAction::VaultAccessGranted, $vault),
                );
            }
            $invitation->update(['accepted_at' => now()]);
            $this->audit->record($invitation->workspace, $user, AuditAction::InvitationAccepted, $invitation);

            return true;
        });

        if (! $accepted) {
            throw ValidationException::withMessages(['invitation' => 'This invitation has expired.']);
        }
    }

    public function canBeViewedBy(User $user, WorkspaceInvitation $invitation, #[\SensitiveParameter] string $token): bool
    {
        return $this->isViewable($invitation, $token)
            && hash_equals($invitation->email, Str::lower(trim($user->email)))
            && $user->workspace_id !== $invitation->workspace_id
            && $invitation->expires_at->isFuture();
    }

    public function isViewable(WorkspaceInvitation $invitation, #[\SensitiveParameter] string $token): bool
    {
        return hash_equals($invitation->token_hash, hash('sha256', $token))
            && $invitation->accepted_at === null
            && $invitation->revoked_at === null
            && $invitation->expired_at === null;
    }

    public function registerAndAccept(
        WorkspaceInvitation $invitation,
        #[\SensitiveParameter] string $token,
        #[\SensitiveParameter] string $password,
    ): User {
        return DB::transaction(function () use ($invitation, $token, $password): User {
            $user = User::query()->where('email', $invitation->email)->lockForUpdate()->first();

            if ($user && $user->password !== null) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this invitation. Sign in to continue.',
                ]);
            }

            if ($user) {
                $user->forceFill(['password' => $password])->save();
            } else {
                $user = User::query()->create([
                    'email' => $invitation->email,
                    'password' => $password,
                ]);
            }

            $this->accept($user, $invitation, $token);

            return $user;
        });
    }

    public function revoke(User $actor, WorkspaceInvitation $invitation): void
    {
        $this->authorizeAdmin($actor);

        DB::transaction(function () use ($actor, $invitation): void {
            $invitation = WorkspaceInvitation::query()->lockForUpdate()->findOrFail($invitation->getKey());
            if ($invitation->workspace_id !== $actor->workspace_id) {
                throw new AuthorizationException;
            }
            if (! $invitation->isPending()) {
                throw ValidationException::withMessages(['invitation' => 'Only a pending invitation can be revoked.']);
            }

            $invitation->update(['revoked_at' => now()]);
            $this->audit->record($actor->workspace, $actor, AuditAction::InvitationRevoked, $invitation);
        });
    }

    public function expireOutstanding(User $actor): void
    {
        $this->authorizeAdmin($actor);

        WorkspaceInvitation::query()
            ->where('workspace_id', $actor->workspace_id)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->whereNull('expired_at')
            ->where('expires_at', '<=', now())
            ->each(function (WorkspaceInvitation $invitation) use ($actor): void {
                $invitation->update(['expired_at' => now()]);
                $this->audit->record($actor->workspace, $actor, AuditAction::InvitationExpired, $invitation);
            });
    }

    /** @param array<int, int> $vaultIds @return array<int, int> */
    private function validatedVaultIds(User $actor, WorkspaceRole $role, array $vaultIds): array
    {
        if ($role === WorkspaceRole::Admin) {
            return [];
        }

        $vaultIds = array_values(array_unique(array_map('intval', $vaultIds)));
        if ($vaultIds === []) {
            throw ValidationException::withMessages(['vault_ids' => 'Select at least one vault for a member.']);
        }

        $valid = Vault::query()
            ->where('workspace_id', $actor->workspace_id)
            ->active()
            ->whereKey($vaultIds)
            ->pluck('id')
            ->all();

        if (count($valid) !== count($vaultIds)) {
            throw ValidationException::withMessages(['vault_ids' => 'Choose active vaults from this workspace.']);
        }

        return $valid;
    }

    private function authorizeAdmin(User $actor): void
    {
        if (! $actor->workspace_id || ! $actor->isWorkspaceAdmin()) {
            throw new AuthorizationException;
        }
    }

    private function leaveCurrentWorkspace(User $user): void
    {
        $workspace = $user->workspace;

        if ($user->isWorkspaceAdmin()) {
            $adminCount = User::query()
                ->where('workspace_id', $workspace->getKey())
                ->where('role', WorkspaceRole::Admin)
                ->lockForUpdate()
                ->count();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'invitation' => 'Add another admin to your current workspace before leaving it.',
                ]);
            }
        }

        $accessibleVaults = $user->isWorkspaceAdmin()
            ? $workspace->vaults()->get()
            : $user->vaults()->get();

        $this->rotationTasks->createForRevokedVaults(
            $user,
            $user,
            $accessibleVaults,
            RotationReason::MemberRemoved,
        );
        $this->audit->record($workspace, $user, AuditAction::MemberRemoved, $user);
        $user->vaults()->detach();
    }
}

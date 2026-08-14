<?php

namespace App\Services\Membership;

use App\Enums\AuditAction;
use App\Enums\RotationReason;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Vault;
use App\Services\Audit\AuditLogger;
use App\Services\Rotation\RotationTaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MemberService
{
    public function __construct(
        private AuditLogger $audit,
        private RotationTaskService $rotationTasks,
    ) {}

    /** @param array<int, int> $vaultIds */
    public function update(User $actor, User $member, WorkspaceRole $role, array $vaultIds): void
    {
        $this->authorizeTarget($actor, $member);

        DB::transaction(function () use ($actor, $member, $role, $vaultIds): void {
            $member = User::query()->lockForUpdate()->findOrFail($member->getKey());
            $this->authorizeTarget($actor, $member);
            $this->ensureLastAdminRemains($member, $role);
            $newVaultIds = $this->validatedVaultIds($actor, $role, $vaultIds);
            $previousVaults = $this->accessibleVaults($member);
            $removedVaults = $previousVaults->whereNotIn('id', $role === WorkspaceRole::Admin
                ? $actor->workspace->vaults()->pluck('id')->all()
                : $newVaultIds);

            $currentGrantIds = $member->vaults()->pluck('vaults.id')->all();
            $grantedIds = array_diff($newVaultIds, $currentGrantIds);
            $revokedIds = array_diff($currentGrantIds, $newVaultIds);

            $member->forceFill(['role' => $role])->save();
            $member->vaults()->syncWithPivotValues($newVaultIds, ['granted_by_user_id' => $actor->getKey()]);

            Vault::query()->whereKey($grantedIds)->each(
                fn (Vault $vault) => $this->audit->record($actor->workspace, $actor, AuditAction::VaultAccessGranted, $vault),
            );
            Vault::query()->whereKey($revokedIds)->each(
                fn (Vault $vault) => $this->audit->record($actor->workspace, $actor, AuditAction::VaultAccessRevoked, $vault),
            );

            $this->rotationTasks->createForRevokedVaults($actor, $member, $removedVaults, RotationReason::VaultAccessRemoved);
            $this->audit->record($actor->workspace, $actor, AuditAction::MemberAccessUpdated, $member);
        });
    }

    public function remove(User $actor, User $member, string $confirmation): int
    {
        $this->authorizeTarget($actor, $member);

        if (! hash_equals($member->email, trim($confirmation))) {
            throw ValidationException::withMessages(['confirmation' => 'Enter the member email exactly to confirm removal.']);
        }

        return DB::transaction(function () use ($actor, $member): int {
            $member = User::query()->lockForUpdate()->findOrFail($member->getKey());
            $this->authorizeTarget($actor, $member);
            $this->ensureLastAdminRemains($member, WorkspaceRole::Member);
            $accessibleVaults = $this->accessibleVaults($member);

            $taskCount = $this->rotationTasks->createForRevokedVaults(
                $actor,
                $member,
                $accessibleVaults,
                RotationReason::MemberRemoved,
            );
            $this->audit->record($actor->workspace, $actor, AuditAction::MemberRemoved, $member);
            $member->vaults()->detach();
            DB::table((string) config('session.table', 'sessions'))->where('user_id', $member->getKey())->delete();
            $member->forceFill(['workspace_id' => null, 'role' => null])->save();

            return $taskCount;
        });
    }

    /** @return Collection<int, Vault> */
    private function accessibleVaults(User $member): Collection
    {
        return $member->isWorkspaceAdmin()
            ? $member->workspace->vaults()->get()
            : $member->vaults()->get();
    }

    /** @param array<int, int> $vaultIds @return array<int, int> */
    private function validatedVaultIds(User $actor, WorkspaceRole $role, array $vaultIds): array
    {
        if ($role === WorkspaceRole::Admin) {
            return [];
        }

        $vaultIds = array_values(array_unique(array_map('intval', $vaultIds)));
        if ($vaultIds === []) {
            throw ValidationException::withMessages(['vault_ids' => 'Select at least one vault when assigning the member role.']);
        }

        $valid = $actor->workspace->vaults()->active()->whereKey($vaultIds)->pluck('id')->all();
        if (count($valid) !== count($vaultIds)) {
            throw ValidationException::withMessages(['vault_ids' => 'Choose active vaults from this workspace.']);
        }

        return $valid;
    }

    private function ensureLastAdminRemains(User $member, WorkspaceRole $role): void
    {
        if (! $member->isWorkspaceAdmin() || $role === WorkspaceRole::Admin) {
            return;
        }

        $adminCount = User::query()
            ->where('workspace_id', $member->workspace_id)
            ->where('role', WorkspaceRole::Admin)
            ->lockForUpdate()
            ->count();

        if ($adminCount <= 1) {
            throw ValidationException::withMessages(['role' => 'The workspace must always have at least one admin.']);
        }
    }

    private function authorizeTarget(User $actor, User $member): void
    {
        if (! $actor->isWorkspaceAdmin()
            || $actor->workspace_id === null
            || $actor->workspace_id !== $member->workspace_id
            || $actor->is($member)) {
            throw new AuthorizationException;
        }
    }
}

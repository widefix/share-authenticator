<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;

class VaultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null && $user->isWorkspaceAdmin();
    }

    public function view(User $user, Vault $vault): bool
    {
        return $user->canAccessVault($vault);
    }

    public function create(User $user): bool
    {
        return $user->workspace_id !== null && $user->isWorkspaceAdmin();
    }

    public function update(User $user, Vault $vault): bool
    {
        return $this->manage($user, $vault);
    }

    public function delete(User $user, Vault $vault): bool
    {
        return $this->manage($user, $vault);
    }

    private function manage(User $user, Vault $vault): bool
    {
        return $user->isWorkspaceAdmin()
            && $user->workspace_id === $vault->workspace_id;
    }
}

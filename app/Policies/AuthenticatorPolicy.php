<?php

namespace App\Policies;

use App\Models\Authenticator;
use App\Models\User;

class AuthenticatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null && $user->isWorkspaceAdmin();
    }

    public function view(User $user, Authenticator $authenticator): bool
    {
        return $this->manage($user, $authenticator);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Authenticator $authenticator): bool
    {
        return $this->manage($user, $authenticator);
    }

    public function delete(User $user, Authenticator $authenticator): bool
    {
        return $this->manage($user, $authenticator);
    }

    private function manage(User $user, Authenticator $authenticator): bool
    {
        return $user->isWorkspaceAdmin()
            && $user->workspace_id === $authenticator->vault()->value('workspace_id');
    }
}

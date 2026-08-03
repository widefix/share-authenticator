<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspace_id === $workspace->getKey();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace) && $user->isWorkspaceAdmin();
    }
}

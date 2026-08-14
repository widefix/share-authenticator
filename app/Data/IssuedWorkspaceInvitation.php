<?php

namespace App\Data;

use App\Models\WorkspaceInvitation;

final readonly class IssuedWorkspaceInvitation
{
    public function __construct(
        public WorkspaceInvitation $invitation,
        #[\SensitiveParameter] public string $token,
    ) {}

    public function url(): string
    {
        return route('invitations.show', [$this->invitation, $this->token]);
    }
}

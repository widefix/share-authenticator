<?php

namespace App\Http\Controllers;

use App\Http\Responses\MembershipResponseFactory;
use App\Services\Membership\InvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class MembershipController
{
    public function __construct(
        private InvitationService $invitations,
        private MembershipResponseFactory $responses,
    ) {}

    public function __invoke(Request $request): View
    {
        $actor = $request->user();
        if (! $actor->isWorkspaceAdmin() || ! $actor->workspace_id) {
            throw new AuthorizationException;
        }

        $this->invitations->expireOutstanding($actor);
        $workspace = $actor->workspace;

        return $this->responses->index(
            $actor,
            $workspace->users()->with('vaults')->orderBy('email')->get(),
            $workspace->vaults()->active()->orderBy('name')->get(),
            $workspace->invitations()->with('vaults')->latest()->limit(50)->get(),
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\RegisterFromInvitationRequest;
use App\Http\Requests\StoreWorkspaceInvitationRequest;
use App\Http\Responses\MembershipResponseFactory;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Services\Membership\InvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final readonly class WorkspaceInvitationController
{
    public function __construct(
        private InvitationService $invitations,
        private MembershipResponseFactory $responses,
    ) {}

    public function store(StoreWorkspaceInvitationRequest $request): RedirectResponse
    {
        $issued = $this->invitations->invite(
            $request->user(),
            $request->string('email')->toString(),
            WorkspaceRole::from($request->string('role')->toString()),
            $request->input('vault_ids', []),
        );

        return $this->responses->invited($issued->url());
    }

    public function show(Request $request, WorkspaceInvitation $invitation, string $token): View|RedirectResponse
    {
        if (! $this->invitations->isViewable($invitation, $token)) {
            throw new AuthorizationException;
        }

        $invitation->load('workspace', 'vaults');
        $user = $request->user();

        if ($user) {
            if (! $this->invitations->canBeViewedBy($user, $invitation, $token)) {
                throw new AuthorizationException;
            }

            return $this->responses->invitation($invitation, $token, $user);
        }

        $existing = User::query()->where('email', $invitation->email)->first();
        if ($existing?->password !== null) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login')->with('status', 'Sign in to review your workspace invitation.');
        }

        return $this->responses->invitationRegistration($invitation, $token);
    }

    public function register(RegisterFromInvitationRequest $request, WorkspaceInvitation $invitation, string $token): RedirectResponse
    {
        $user = $this->invitations->registerAndAccept(
            $invitation,
            $token,
            $request->string('password')->toString(),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return $this->responses->accepted();
    }

    public function accept(Request $request, WorkspaceInvitation $invitation, string $token): RedirectResponse
    {
        $this->invitations->accept($request->user(), $invitation, $token);

        return $this->responses->accepted();
    }

    public function revoke(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        $this->invitations->revoke($request->user(), $invitation);

        return $this->responses->revoked();
    }
}

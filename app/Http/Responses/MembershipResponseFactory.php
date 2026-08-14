<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class MembershipResponseFactory
{
    public function index(User $actor, Collection $members, Collection $vaults, Collection $invitations): View
    {
        return view('members.index', compact('actor', 'members', 'vaults', 'invitations'));
    }

    public function invitation(WorkspaceInvitation $invitation, string $token, User $user): View
    {
        return view('invitations.show', compact('invitation', 'token', 'user'));
    }

    public function invitationRegistration(WorkspaceInvitation $invitation, string $token): View
    {
        return view('invitations.register', compact('invitation', 'token'));
    }

    public function invited(string $url): RedirectResponse
    {
        return $this->members('Invitation link created.')
            ->with('invitation_link', $url);
    }

    public function revoked(): RedirectResponse
    {
        return $this->members('Invitation revoked.');
    }

    public function updated(): RedirectResponse
    {
        return $this->members('Member access updated.');
    }

    public function removed(int $rotationTasks): RedirectResponse
    {
        return $this->members("Member removed. {$rotationTasks} rotation tasks were created.");
    }

    public function accepted(): RedirectResponse
    {
        return redirect()->route('dashboard')->with('status', 'Invitation accepted. Welcome to the workspace.');
    }

    private function members(string $status): RedirectResponse
    {
        return redirect()->route('members.index')->with('status', $status);
    }
}

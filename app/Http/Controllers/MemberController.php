<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\RemoveMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Responses\MembershipResponseFactory;
use App\Models\User;
use App\Services\Membership\MemberService;
use Illuminate\Http\RedirectResponse;

final readonly class MemberController
{
    public function __construct(
        private MemberService $members,
        private MembershipResponseFactory $responses,
    ) {}

    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $this->members->update(
            $request->user(),
            $member,
            WorkspaceRole::from($request->string('role')->toString()),
            $request->input('vault_ids', []),
        );

        return $this->responses->updated();
    }

    public function destroy(RemoveMemberRequest $request, User $member): RedirectResponse
    {
        return $this->responses->removed($this->members->remove(
            $request->user(),
            $member,
            $request->string('confirmation')->toString(),
        ));
    }
}

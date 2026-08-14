<x-layouts.app title="Members">
    <header class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="eyebrow">{{ $actor->workspace->name }}</p>
            <h1 class="display-xl mt-3 text-ink">Members and access</h1>
            <p class="body-lead mt-3 max-w-2xl">Invite teammates, choose their vaults, and revoke access when responsibilities change.</p>
        </div>
        <p class="caption whitespace-nowrap">{{ $members->count() }} {{ Str::plural('member', $members->count()) }}</p>
    </header>

    @if (session('status'))
        <div class="alert alert-success mt-7" role="status">{{ session('status') }}</div>
    @endif

    @if (session('invitation_link'))
        <div class="card mt-4 p-4" role="status">
            <p class="heading-sm text-ink">Share this invitation link</p>
            <p class="body-mute mt-1">The link is shown only now. Send it to the invited person through a channel you trust.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input type="text" readonly value="{{ session('invitation_link') }}" class="input min-w-0 flex-1 font-mono text-xs" data-invitation-link>
                <button type="button" class="btn btn-neutral" data-copy-invitation-link>Copy link</button>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error mt-7" role="alert">
            <div>
                <p class="font-semibold">We couldn't save that change.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="mt-9 grid gap-8 lg:grid-cols-[21rem_minmax(0,1fr)] lg:items-start">
        <div class="card card-raised p-6 lg:sticky lg:top-24">
            <p class="eyebrow">New invitation</p>
            <h2 class="display-md mt-3 text-ink">Invite a teammate</h2>

            <form method="POST" action="{{ route('invitations.store') }}" class="mt-6 space-y-4" data-submitting-form>
                @csrf

                <div class="field">
                    <label for="invite-email" class="field-label">Email</label>
                    <input id="invite-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input mt-2" placeholder="teammate@company.com">
                </div>

                <div class="field">
                    <label for="invite-role" class="field-label">Role</label>
                    <select id="invite-role" name="role" class="select mt-2">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                    <p class="field-hint">Admins manage vaults, members, and every authenticator.</p>
                </div>

                <fieldset>
                    <legend class="field-label">Initial vault access</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($vaults as $vault)
                            <label class="choice-chip">
                                <input type="checkbox" name="vault_ids[]" value="{{ $vault->id }}" class="checkbox">
                                {{ $vault->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary btn-block" data-submit-button data-loading-label="Creating...">Create invitation link</button>
            </form>
        </div>

        <div class="space-y-4">
            @foreach ($members as $member)
                <article class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="heading-sm truncate text-ink">{{ $member->email }}</h2>
                            <p class="caption mt-1">
                                <span class="pill {{ $member->role->value === 'admin' ? 'pill-indigo' : 'pill-neutral' }}">{{ ucfirst($member->role->value) }}</span>
                                @if ($member->is($actor))
                                    <span class="ml-1">· You</span>
                                @endif
                            </p>
                        </div>
                        @if (! $member->is($actor))
                            <details>
                                <summary class="cursor-pointer text-sm font-medium text-ruby-deep">Remove</summary>
                                <form method="POST" action="{{ route('members.destroy', $member) }}" class="alert-danger-zone mt-3 w-72" data-submitting-form>
                                    @csrf
                                    @method('DELETE')
                                    <p class="text-xs leading-5 text-ruby-deep">Previously copied secrets cannot be erased. Rotation tasks will be created for every authenticator this person could access.</p>
                                    <label for="remove-{{ $member->id }}" class="mt-3 block text-xs font-semibold text-ruby-deep">Type {{ $member->email }}</label>
                                    <input id="remove-{{ $member->id }}" name="confirmation" required autocomplete="off" class="input mt-1">
                                    <button class="btn btn-danger btn-sm mt-3 btn-block" data-submit-button data-loading-label="Removing...">Remove member</button>
                                </form>
                            </details>
                        @endif
                    </div>

                    @if (! $member->is($actor))
                        <form method="POST" action="{{ route('members.update', $member) }}" class="mt-5 border-t border-hairline pt-5" data-submitting-form>
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-4 sm:grid-cols-[10rem_minmax(0,1fr)_auto] sm:items-end">
                                <div class="field">
                                    <label for="role-{{ $member->id }}" class="field-label">Role</label>
                                    <select id="role-{{ $member->id }}" name="role" class="select mt-2">
                                        <option value="member" @selected($member->role->value === 'member')>Member</option>
                                        <option value="admin" @selected($member->role->value === 'admin')>Admin</option>
                                    </select>
                                </div>
                                <fieldset>
                                    <legend class="field-label">Vaults for members</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($vaults as $vault)
                                            <label class="choice-chip">
                                                <input type="checkbox" name="vault_ids[]" value="{{ $vault->id }}" class="checkbox" @checked($member->vaults->contains($vault))>
                                                {{ $vault->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <button class="btn btn-neutral btn-sm" data-submit-button data-loading-label="Saving...">Save access</button>
                            </div>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-12 border-t border-hairline pt-8">
        <h2 class="heading-md text-ink">Invitations</h2>
        <div class="card mt-4 divide-y divide-hairline overflow-hidden">
            @forelse ($invitations as $invitation)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4 sm:px-5">
                    <div class="min-w-0">
                        <p class="heading-sm truncate text-ink">{{ $invitation->email }}</p>
                        <p class="caption mt-1">{{ ucfirst($invitation->role->value) }} · @if($invitation->accepted_at) Accepted @elseif($invitation->revoked_at) Revoked @elseif($invitation->expired_at) Expired @else Expires {{ $invitation->expires_at->diffForHumans() }} @endif</p>
                    </div>
                    @if ($invitation->isPending())
                        <form method="POST" action="{{ route('invitations.revoke', $invitation) }}">
                            @csrf
                            <button class="btn btn-danger-quiet btn-sm">Revoke</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="body-mute p-5">No invitations yet.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>

<x-layouts.app title="Workspace invitation">
    <div class="mx-auto max-w-xl">
        <p class="eyebrow">Workspace invitation</p>
        <h1 class="display-xl mt-3 text-ink">Join {{ $invitation->workspace->name }}</h1>
        <p class="body-lead mt-4">
            This invitation is for <strong class="font-medium text-ink">{{ $user->email }}</strong> and grants
            @if ($invitation->role === \App\Enums\WorkspaceRole::Admin)
                administrator access to every vault.
            @else
                member access to {{ $invitation->vaults->count() }} {{ Str::plural('vault', $invitation->vaults->count()) }}.
            @endif
        </p>

        @if ($user->workspace_id)
            <div class="alert alert-warning mt-6" role="status">
                Accepting this invitation moves your account out of <strong>{{ $user->workspace->name }}</strong>. Your previous vault access will be revoked and its administrators may need to rotate authenticators you could access.
            </div>
        @endif

        @error('invitation')
            <div class="alert alert-error mt-6" role="alert">{{ $message }}</div>
        @enderror

        <div class="card card-raised mt-8 p-6">
            <p class="caption">Expires {{ $invitation->expires_at->diffForHumans() }}</p>
            <form method="POST" action="{{ route('invitations.accept', [$invitation, $token]) }}" class="mt-5" data-submitting-form>
                @csrf
                <button type="submit" class="btn btn-primary btn-lg btn-block" data-submit-button data-loading-label="Joining...">Accept invitation</button>
            </form>
        </div>
    </div>
</x-layouts.app>

<x-layouts.auth title="Join workspace">
    <header>
        <p class="eyebrow">Workspace invitation</p>
        <h1 class="display-lg mt-3 text-ink">Join {{ $invitation->workspace->name }}</h1>
        <p class="body-text mt-3">
            Create a password for <strong class="font-medium text-ink">{{ $invitation->email }}</strong> to accept this invitation.
        </p>
    </header>

    @if ($errors->any())
        <div class="alert alert-error mt-6" role="alert">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('invitations.register', [$invitation, $token]) }}" class="mt-7 space-y-5" data-submitting-form>
        @csrf
        <div class="field">
            <label for="password" class="field-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required autofocus class="input mt-2">
        </div>
        <div class="field">
            <label for="password-confirmation" class="field-label">Confirm password</label>
            <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required class="input mt-2">
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block" data-submit-button data-loading-label="Joining...">Create account and join</button>
    </form>
</x-layouts.auth>

<x-layouts.auth title="Create account">
    <header>
        <p class="eyebrow">Get started</p>
        <h1 class="display-lg mt-3 text-ink">Create your account</h1>
        <p class="body-text mt-3">Register with an email address and a password of at least 12 characters.</p>
    </header>

    <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5" data-submitting-form>
        @csrf

        <div class="field">
            <label for="email" class="field-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="input mt-2 {{ $errors->has('email') ? 'input-invalid' : '' }}">
            @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="password" class="field-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required class="input mt-2 {{ $errors->has('password') ? 'input-invalid' : '' }}">
            @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="password-confirmation" class="field-label">Confirm password</label>
            <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required class="input mt-2">
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" data-submit-button data-loading-label="Creating account...">Create account</button>
    </form>

    <p class="body-mute mt-6 text-center">
        Already registered? <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">Sign in</a>
    </p>
</x-layouts.auth>

<x-layouts.auth title="Sign in">
    <header>
        <p class="eyebrow">Welcome back</p>
        <h1 class="display-lg mt-3 text-ink">Sign in to your account</h1>
        <p class="body-text mt-3">Use your email address and password.</p>
    </header>

    @if (session('status'))
        <div class="alert alert-success mt-6" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5" data-submitting-form>
        @csrf

        <div class="field">
            <label for="email" class="field-label">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                inputmode="email"
                required
                autofocus
                @error('email') aria-describedby="email-error" @enderror
                @error('email') aria-invalid="true" @enderror
                class="input mt-2 h-11 {{ $errors->has('email') ? 'input-invalid' : '' }}"
                placeholder="you@company.com"
            >
            @error('email')
                <p id="email-error" class="field-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password" class="field-label">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="input mt-2"
            >
        </div>

        <button
            type="submit"
            class="btn btn-primary btn-lg btn-block"
            data-submit-button
            data-loading-label="Signing in..."
        >
            Sign in
        </button>
    </form>

    <p class="body-mute mt-6 text-center">
        New here? <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">Create an account</a>
    </p>
</x-layouts.auth>

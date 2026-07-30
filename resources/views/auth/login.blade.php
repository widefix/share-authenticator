<x-layouts.auth title="Sign in">
    <header>
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Passwordless access</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-950 dark:text-stone-50">Sign in with your email</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">
            We will send a six-digit code. No password needed.
        </p>
    </header>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5" data-submitting-form>
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                inputmode="email"
                required
                autofocus
                aria-describedby="email-help @error('email') email-error @enderror"
                @error('email') aria-invalid="true" @enderror
                class="mt-2 block h-12 w-full rounded-xl border bg-white px-3.5 text-base text-stone-950 outline-none transition-colors placeholder:text-stone-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-500 {{ $errors->has('email') ? 'border-red-500' : 'border-stone-300 dark:border-stone-700' }}"
                placeholder="you@company.com"
            >
            <p id="email-help" class="mt-2 text-xs leading-5 text-stone-500 dark:text-stone-400">Use the address connected to your team.</p>
            @error('email')
                <p id="email-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white outline-none transition-colors hover:bg-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-wait disabled:opacity-70 dark:bg-emerald-500 dark:text-stone-950 dark:hover:bg-emerald-400"
            data-submit-button
            data-loading-label="Sending code..."
        >
            Email me a code
        </button>
    </form>
</x-layouts.auth>

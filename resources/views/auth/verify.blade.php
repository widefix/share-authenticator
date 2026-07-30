<x-layouts.auth title="Enter your code">
    <header>
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Email sent</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-950 dark:text-stone-50">Check your inbox</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">
            Enter the code sent to <span class="font-semibold text-stone-800 dark:text-stone-100">{{ $maskedEmail }}</span>.
        </p>
    </header>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($deliveryFailed)
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert">
            We could not deliver that email. Check the address or request another code.
        </div>
    @endif

    <form method="POST" action="{{ route('login.authenticate') }}" class="mt-7 space-y-5" data-submitting-form>
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Six-digit code</label>
            <input
                id="code"
                name="code"
                type="text"
                value="{{ old('code') }}"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                autofocus
                aria-describedby="code-expiry @error('code') code-error @enderror"
                @error('code') aria-invalid="true" @enderror
                class="mt-2 block h-14 w-full rounded-xl border bg-white px-4 text-center font-mono text-2xl font-semibold tracking-[0.32em] text-stone-950 outline-none transition-colors placeholder:text-stone-300 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:bg-stone-950 dark:text-stone-100 dark:placeholder:text-stone-700 {{ $errors->has('code') ? 'border-red-500' : 'border-stone-300 dark:border-stone-700' }}"
                placeholder="000000"
                data-otp-input
            >
            <p
                id="code-expiry"
                class="mt-2 text-xs leading-5 text-stone-500 dark:text-stone-400"
                data-expiry-target="{{ $expiresAt }}"
            >
                This code expires in 10:00.
            </p>
            @error('code')
                <p id="code-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white outline-none transition-colors hover:bg-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-wait disabled:opacity-70 dark:bg-emerald-500 dark:text-stone-950 dark:hover:bg-emerald-400"
            data-submit-button
            data-loading-label="Verifying..."
        >
            Verify and sign in
        </button>
    </form>

    <div class="mt-6 border-t border-stone-200 pt-5 dark:border-stone-800">
        <form method="POST" action="{{ route('login.resend') }}" data-submitting-form>
            @csrf
            <button
                type="submit"
                class="w-full rounded-xl px-3 py-2 text-sm font-semibold text-emerald-700 outline-none transition-colors hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:cursor-not-allowed disabled:text-stone-400 disabled:hover:bg-transparent dark:text-emerald-400 dark:hover:bg-emerald-950 dark:disabled:text-stone-600"
                data-resend-button
                data-resend-target="{{ $resendAt }}"
                data-submit-button
                data-loading-label="Sending..."
            >
                Send another code
            </button>
            @error('resend')
                <p class="mt-2 text-center text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </form>

        <a href="{{ route('login') }}" class="mt-2 block rounded-xl px-3 py-2 text-center text-sm font-medium text-stone-600 outline-none transition-colors hover:bg-stone-100 hover:text-stone-950 focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-50">
            Use another email
        </a>
    </div>
</x-layouts.auth>

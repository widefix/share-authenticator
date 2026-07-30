<x-layouts.auth title="Dashboard">
    <header>
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Signed in</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-950 dark:text-stone-50">Your workspace is ready</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">
            You are signed in as <span class="font-semibold text-stone-800 dark:text-stone-100">{{ auth()->user()->email }}</span>.
        </p>
    </header>

    <div class="mt-7 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm leading-6 text-stone-600 dark:border-stone-800 dark:bg-stone-950 dark:text-stone-300">
        Vaults and team access will appear here as the product is built.
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6" data-submitting-form>
        @csrf
        <button
            type="submit"
            class="flex h-11 w-full items-center justify-center rounded-xl border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 outline-none transition-colors hover:bg-stone-100 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-wait disabled:opacity-70 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:hover:bg-stone-800"
            data-submit-button
            data-loading-label="Signing out..."
        >
            Sign out
        </button>
    </form>
</x-layouts.auth>

<x-layouts.app :title="$vault->name">
    <header class="flex flex-col gap-6 border-b border-stone-200 pb-8 sm:flex-row sm:items-end sm:justify-between dark:border-stone-800">
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-stone-500 underline-offset-4 hover:text-stone-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-400 dark:hover:text-white">← Back to dashboard</a>
            <p class="mt-5 text-sm font-semibold text-emerald-700 dark:text-emerald-400">Vault</p>
            <h1 class="mt-2 text-4xl font-semibold tracking-[-0.035em] text-stone-950 dark:text-white">{{ $vault->name }}</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-stone-500 dark:text-stone-400">Reveal only the code you need. Secrets remain encrypted and are never sent to this page.</p>
        </div>
        <a href="{{ route('vaults.authenticators.create', $vault) }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-stone-900 px-5 text-sm font-semibold text-white outline-none transition-colors hover:bg-stone-700 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px dark:bg-stone-100 dark:text-stone-950 dark:hover:bg-white dark:focus-visible:ring-offset-stone-950">Add authenticator</a>
    </header>

    @if (session('status'))
        <div class="mt-7 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100" role="status">{{ session('status') }}</div>
    @endif

    <section
        class="mt-9"
        data-authenticator-index
        data-feed-url="{{ route('api.vaults.authenticators.index', $vault) }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative max-w-md flex-1">
                <label for="authenticator-search" class="sr-only">Search authenticators</label>
                <input id="authenticator-search" type="search" autocomplete="off" placeholder="Search issuer or account" class="h-11 w-full rounded-xl border border-stone-300 bg-white px-4 text-sm text-stone-950 outline-none transition-colors placeholder:text-stone-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-900 dark:text-white" data-authenticator-search>
            </div>
            <p class="text-sm tabular-nums text-stone-500 dark:text-stone-400"><span data-active-count>{{ $vault->active_authenticators_count }}</span> active · <span data-archived-count>{{ $vault->archived_authenticators_count }}</span> archived</p>
        </div>

        <div class="mt-6 rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900" data-authenticator-loading aria-live="polite">
            <div class="animate-pulse space-y-4 p-6"><div class="h-5 w-40 rounded bg-stone-200 dark:bg-stone-800"></div><div class="h-12 rounded bg-stone-100 dark:bg-stone-950"></div></div>
        </div>

        <div class="hidden" data-authenticator-content>
            <div class="grid gap-4 lg:grid-cols-2" data-active-list></div>
            <div class="hidden rounded-2xl border border-dashed border-stone-300 px-6 py-14 text-center dark:border-stone-700" data-active-empty>
                <p class="font-semibold text-stone-900 dark:text-stone-100">No authenticators found</p>
                <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Add the first authenticator or adjust your search.</p>
            </div>

            <section class="mt-14 hidden border-t border-stone-200 pt-9 dark:border-stone-800" data-archived-section>
                <h2 class="text-xl font-semibold tracking-tight text-stone-950 dark:text-white">Archived authenticators</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2" data-archived-list></div>
            </section>
        </div>

        <div class="mt-6 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert" data-authenticator-error></div>
        <div class="mt-6 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100" role="status" data-clock-warning>Your device clock differs from the server. Enable automatic date and time to avoid confusing countdowns.</div>
        <p class="sr-only" aria-live="polite" data-authenticator-announcer></p>
    </section>
</x-layouts.app>

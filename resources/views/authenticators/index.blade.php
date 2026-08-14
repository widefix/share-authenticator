<x-layouts.app :title="$vault->name">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="link-quiet"><span aria-hidden="true">←</span> Vaults</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <span class="text-ink-secondary">{{ $vault->name }}</span>
    </nav>

    <header class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="eyebrow">Vault</p>
            <h1 class="display-xl mt-3 text-ink">{{ $vault->name }}</h1>
            <p class="body-lead mt-3 max-w-xl">Reveal only the code you need. Secrets remain encrypted and are never sent to this page.</p>
        </div>
        @if (auth()->user()->isWorkspaceAdmin())
            <a href="{{ route('vaults.authenticators.create', $vault) }}" class="btn btn-primary self-start sm:self-auto">
                <span aria-hidden="true">+</span> Add authenticator
            </a>
        @endif
    </header>

    @if (session('status'))
        <div class="alert alert-success mt-7" role="status">{{ session('status') }}</div>
    @endif

    <section
        class="mt-8"
        data-authenticator-index
        data-feed-url="{{ route('api.vaults.authenticators.index', $vault) }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full max-w-md">
                <label for="authenticator-search" class="sr-only">Search authenticators</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-mute" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5 14 14" stroke-linecap="round"/>
                </svg>
                <input
                    id="authenticator-search"
                    type="search"
                    autocomplete="off"
                    placeholder="Search issuer or account"
                    class="input h-10 pl-9 pr-12"
                    data-authenticator-search
                >
                <kbd class="pointer-events-none absolute right-3 top-1/2 max-sm:hidden block -translate-y-1/2 rounded border border-hairline bg-canvas-soft px-1.5 py-0.5 text-[0.6875rem] font-medium text-ink-mute">/</kbd>
            </div>
            <p class="caption whitespace-nowrap">
                <span data-active-count>{{ $vault->active_authenticators_count }}</span> active
                <span aria-hidden="true" class="mx-1 text-hairline-input">·</span>
                <span data-archived-count>{{ $vault->archived_authenticators_count }}</span> archived
            </p>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2" data-authenticator-loading aria-live="polite" aria-busy="true">
            <span class="sr-only">Loading authenticators…</span>
            @for ($placeholder = 0; $placeholder < 2; $placeholder++)
                <div class="card p-5" aria-hidden="true">
                    <div class="skeleton h-3 w-24"></div>
                    <div class="skeleton mt-3 h-5 w-44"></div>
                    <div class="skeleton mt-6 h-14 w-full"></div>
                    <div class="skeleton mt-4 h-9 w-40"></div>
                </div>
            @endfor
        </div>

        <div class="hidden" data-authenticator-content>
            <div class="mt-5 grid gap-4 lg:grid-cols-2" data-active-list></div>
            <div class="panel-empty mt-5 hidden" data-active-empty>
                <p class="heading-sm text-ink">No authenticators found</p>
                <p class="body-mute mt-2">Add the first authenticator or adjust your search.</p>
                @if (auth()->user()->isWorkspaceAdmin())
                    <a href="{{ route('vaults.authenticators.create', $vault) }}" class="btn btn-secondary mt-5">Add authenticator</a>
                @endif
            </div>

            <section class="mt-12 hidden border-t border-hairline pt-8" data-archived-section>
                <h2 class="heading-md text-ink">Archived authenticators</h2>
                <p class="body-mute mt-1">Archived entries stop generating codes until they are restored.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2" data-archived-list></div>
            </section>
        </div>

        <div class="alert alert-error mt-5 hidden" role="alert" data-authenticator-error></div>
        <div class="alert alert-warning mt-5 hidden" role="status" data-clock-warning>
            Your device clock differs from the server. Enable automatic date and time to avoid confusing countdowns.
        </div>
        <p class="sr-only" aria-live="polite" data-authenticator-announcer></p>
    </section>
</x-layouts.app>

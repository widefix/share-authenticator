@props(['title'])

<!DOCTYPE html>
<html lang="en" class="scheme-light dark:scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Manage shared authenticator vaults for your team.">

        <title>{{ $title }} | {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-stone-100 text-stone-950 antialiased dark:bg-stone-950 dark:text-stone-100">
        <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white focus:not-sr-only">
            Skip to content
        </a>

        <header class="border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-800 dark:bg-stone-950/90">
            <nav class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
                <a href="{{ route('dashboard') }}" aria-label="Shared Authenticator dashboard" class="inline-flex items-center gap-3 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-950">
                    <span class="grid size-9 place-items-center rounded-xl bg-emerald-700 text-xs font-bold tracking-tight text-white dark:bg-emerald-500 dark:text-stone-950" aria-hidden="true">SA</span>
                    <span class="hidden text-sm font-semibold tracking-tight text-stone-900 sm:inline dark:text-stone-100">Shared Authenticator</span>
                </a>

                <div class="flex items-center gap-2 sm:gap-4">
                    @if (auth()->user()->workspace_id && auth()->user()->isWorkspaceAdmin())
                        <a
                            href="{{ route('vaults.index') }}"
                            aria-label="Vault settings"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 outline-none transition-colors hover:bg-stone-100 hover:text-stone-950 focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white {{ request()->routeIs('vaults.*', 'authenticators.*') ? 'bg-stone-100 text-stone-950 dark:bg-stone-800 dark:text-white' : '' }}"
                            @if (request()->routeIs('vaults.*', 'authenticators.*')) aria-current="page" @endif
                        >
                            <span class="sm:hidden">Vaults</span>
                            <span class="hidden sm:inline">Vault settings</span>
                        </a>
                    @endif

                    <span class="hidden max-w-56 truncate text-sm text-stone-500 md:block dark:text-stone-400">{{ auth()->user()->email }}</span>

                    <form method="POST" action="{{ route('logout') }}" data-submitting-form>
                        @csrf
                        <button
                            type="submit"
                            aria-label="Sign out"
                            class="rounded-lg px-3 py-2 text-sm font-semibold text-stone-700 outline-none transition-colors hover:bg-stone-100 hover:text-stone-950 focus-visible:ring-2 focus-visible:ring-emerald-600 active:translate-y-px disabled:cursor-wait disabled:opacity-60 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white"
                            data-submit-button
                            data-loading-label="Signing out..."
                        >
                            <span class="sm:hidden">Exit</span>
                            <span class="hidden sm:inline">Sign out</span>
                        </button>
                    </form>
                </div>
            </nav>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            {{ $slot }}
        </main>
    </body>
</html>

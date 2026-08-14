@props(['title'])

<!DOCTYPE html>
<html lang="en" class="scheme-light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light">
        <meta name="description" content="Manage shared authenticator vaults for your team.">

        <title>{{ $title }} | {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-canvas-soft antialiased">
        <a href="#main-content" class="btn btn-primary btn-sm sr-only fixed left-4 top-4 z-50 focus:not-sr-only">
            Skip to content
        </a>

        <div class="relative">
            <div aria-hidden="true" class="mesh pointer-events-none absolute inset-x-0 top-0 h-72 opacity-60 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>

            <header class="sticky top-0 z-40 border-b border-hairline/80 bg-canvas">
                <nav class="mx-auto flex min-h-16 max-w-6xl items-center gap-3 px-5 sm:px-8" aria-label="Primary navigation">
                    <a href="{{ route('dashboard') }}" aria-label="{{ config('app.name') }} dashboard" class="inline-flex items-center gap-2.5 rounded-lg">
                        <span class="brand-mark" aria-hidden="true">SA</span>
                        <span class="max-sm:hidden text-[0.9375rem] font-medium tracking-tight text-ink">Shared Authenticator</span>
                    </a>

                    @if (auth()->user()->workspace)
                        <span class="pill pill-neutral ml-1 max-lg:hidden max-w-40 truncate" title="{{ auth()->user()->workspace->name }}">
                            {{ auth()->user()->workspace->name }}
                        </span>
                    @endif

                    <div class="ml-auto flex items-center gap-1">
                        @if (auth()->user()->workspace_id)
                            <a
                                href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard', 'vaults.authenticators.*', 'authenticators.*') ? 'nav-link-active' : '' }}"
                                @if (request()->routeIs('dashboard', 'vaults.authenticators.*', 'authenticators.*')) aria-current="page" @endif
                            >
                                Codes
                            </a>

                            @if (auth()->user()->isWorkspaceAdmin())
                                <a
                                    href="{{ route('members.index') }}"
                                    class="nav-link {{ request()->routeIs('members.*', 'invitations.*') ? 'nav-link-active' : '' }}"
                                    @if (request()->routeIs('members.*', 'invitations.*')) aria-current="page" @endif
                                >
                                    Members
                                </a>
                                <a
                                    href="{{ route('vaults.index') }}"
                                    aria-label="Vault settings"
                                    class="nav-link {{ request()->routeIs('vaults.index') ? 'nav-link-active' : '' }}"
                                    @if (request()->routeIs('vaults.index')) aria-current="page" @endif
                                >
                                    <span class="sm:hidden">Vaults</span>
                                    <span class="max-sm:hidden">Vault settings</span>
                                </a>
                            @endif
                        @endif

                        <span class="mx-2 max-md:hidden block h-5 w-px bg-hairline" aria-hidden="true"></span>

                        <span class="max-md:hidden block max-w-48 truncate text-sm text-ink-mute" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>

                        <form method="POST" action="{{ route('logout') }}" data-submitting-form>
                            @csrf
                            <button
                                type="submit"
                                class="btn btn-ghost btn-sm"
                                aria-label="Sign out"
                                data-submit-button
                                data-loading-label="Signing out..."
                            >
                                <span class="sm:hidden">Exit</span>
                                <span class="max-sm:hidden">Sign out</span>
                            </button>
                        </form>
                    </div>
                </nav>
            </header>

            <main id="main-content" class="relative mx-auto w-full max-w-6xl px-5 py-10 sm:px-8 sm:py-12">
                {{ $slot }}
            </main>
        </div>

        <div class="toast-region" data-toast-region aria-live="polite" aria-atomic="false"></div>
    </body>
</html>

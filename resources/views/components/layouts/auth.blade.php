<!DOCTYPE html>
<html lang="en" class="scheme-light dark:scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Sign in' }} | {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-stone-100 text-stone-950 antialiased dark:bg-stone-950 dark:text-stone-100">
        <main class="grid min-h-[100dvh] place-items-center px-4 py-10 sm:px-6">
            <div class="w-full max-w-md">
                <div class="mb-8 inline-flex items-center gap-3 text-sm font-semibold text-stone-900 dark:text-stone-100">
                    <span class="grid size-9 place-items-center rounded-xl bg-emerald-700 text-xs font-bold tracking-tight text-white dark:bg-emerald-500 dark:text-stone-950" aria-hidden="true">SA</span>
                    <span>Shared Authenticator</span>
                </div>

                <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-[0_24px_70px_rgba(41,37,36,0.08)] sm:p-8 dark:border-stone-800 dark:bg-stone-900 dark:shadow-[0_24px_70px_rgba(0,0,0,0.22)]">
                    {{ $slot }}
                </section>

                <p class="mt-6 text-center text-xs leading-5 text-stone-500 dark:text-stone-400">
                    We only use your email to verify your identity and secure your session.
                </p>
            </div>
        </main>
    </body>
</html>

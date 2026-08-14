<!DOCTYPE html>
<html lang="en" class="scheme-light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light">

        <title>{{ $title ?? 'Sign in' }} | {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-canvas-soft antialiased">
        <div class="relative min-h-[100dvh]">
            <div aria-hidden="true" class="mesh pointer-events-none absolute inset-x-0 top-0 h-[26rem] [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>

            <main class="relative mx-auto grid min-h-[100dvh] max-w-md place-items-center px-5 py-12 sm:py-16">
                <div class="w-full">
                    <div class="mb-7 inline-flex items-center gap-2.5">
                        <span class="brand-mark" aria-hidden="true">SA</span>
                        <span class="text-[0.9375rem] font-medium tracking-tight text-ink">Shared Authenticator</span>
                    </div>

                    <section class="card card-raised p-6 sm:p-8">
                        {{ $slot }}
                    </section>

                    <p class="caption mt-5 text-center">Your credentials are protected with secure password hashing and encrypted sessions.</p>
                </div>
            </main>
        </div>

        <div class="toast-region" data-toast-region aria-live="polite" aria-atomic="false"></div>
    </body>
</html>

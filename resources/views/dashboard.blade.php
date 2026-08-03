<x-layouts.app title="Dashboard">
    @if (session('status'))
        <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (! $user->workspace)
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_26rem] lg:gap-20">
            <section class="pt-3">
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">First things first</p>
                <h1 class="mt-3 max-w-2xl text-balance text-4xl font-semibold tracking-[-0.035em] text-stone-950 sm:text-5xl dark:text-white">
                    Create a home for your team’s sign-in codes.
                </h1>
                <p class="mt-5 max-w-xl text-pretty text-base leading-7 text-stone-600 dark:text-stone-300">
                    A workspace keeps your vaults together. We’ll start it with a General vault, and you can organize more as your team grows.
                </p>

                <dl class="mt-10 grid max-w-xl gap-6 sm:grid-cols-2">
                    <div class="border-l-2 border-emerald-600 pl-4">
                        <dt class="text-sm font-semibold text-stone-900 dark:text-stone-100">One workspace</dt>
                        <dd class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-400">Your account belongs to one team workspace in this version.</dd>
                    </div>
                    <div class="border-l-2 border-stone-300 pl-4 dark:border-stone-700">
                        <dt class="text-sm font-semibold text-stone-900 dark:text-stone-100">General included</dt>
                        <dd class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-400">The first vault is created automatically and can be renamed later.</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-[0_24px_70px_rgba(41,37,36,0.08)] sm:p-8 dark:border-stone-800 dark:bg-stone-900 dark:shadow-[0_24px_70px_rgba(0,0,0,0.22)]" aria-labelledby="workspace-form-title">
                <p class="text-xs font-semibold tracking-[0.14em] text-stone-400 uppercase dark:text-stone-500">Workspace setup</p>
                <h2 id="workspace-form-title" class="mt-3 text-2xl font-semibold tracking-tight text-stone-950 dark:text-white">Name your workspace</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500 dark:text-stone-400">Use your team, company, or project name.</p>

                <form method="POST" action="{{ route('workspaces.store') }}" class="mt-7 space-y-5" data-submitting-form>
                    @csrf

                    <div>
                        <label for="workspace-name" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Workspace name</label>
                        <input
                            id="workspace-name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            maxlength="80"
                            autocomplete="organization"
                            required
                            autofocus
                            @error('name') aria-invalid="true" aria-describedby="workspace-name-error" @enderror
                            class="mt-2 block h-12 w-full rounded-xl border bg-white px-3.5 text-base text-stone-950 outline-none transition-colors placeholder:text-stone-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:bg-stone-950 dark:text-stone-100 {{ $errors->has('name') ? 'border-red-500' : 'border-stone-300 dark:border-stone-700' }}"
                            placeholder="Northstar Studio"
                        >
                        @error('name')
                            <p id="workspace-name-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="flex h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white outline-none transition-colors hover:bg-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-wait disabled:opacity-70 dark:bg-emerald-500 dark:text-stone-950 dark:hover:bg-emerald-400"
                        data-submit-button
                        data-loading-label="Creating workspace..."
                    >
                        Create workspace
                    </button>
                </form>
            </section>
        </div>
    @else
        <header class="flex flex-col gap-6 border-b border-stone-200 pb-8 sm:flex-row sm:items-end sm:justify-between dark:border-stone-800">
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ $user->workspace->name }}</p>
                <h1 class="mt-2 text-4xl font-semibold tracking-[-0.035em] text-stone-950 dark:text-white">Your vaults</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-stone-500 dark:text-stone-400">Vaults group the authenticator codes your team needs together.</p>
            </div>

            @if ($user->isWorkspaceAdmin())
                <a href="{{ route('vaults.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-stone-900 px-5 text-sm font-semibold text-white outline-none transition-colors hover:bg-stone-700 focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 active:translate-y-px dark:bg-stone-100 dark:text-stone-950 dark:hover:bg-white dark:focus-visible:ring-offset-stone-950">
                    Manage vaults
                </a>
            @endif
        </header>

        <section class="mt-8" aria-labelledby="active-vaults-title">
            <h2 id="active-vaults-title" class="sr-only">Active vaults</h2>

            @if ($vaults->isEmpty())
                <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-14 text-center dark:border-stone-700">
                    <p class="text-base font-semibold text-stone-900 dark:text-stone-100">No vaults available</p>
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Ask a workspace admin to grant access.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vaults as $vault)
                        <a href="{{ route('vaults.authenticators.index', $vault) }}" class="group block min-h-48 rounded-2xl bg-white p-6 shadow-[0_1px_0_rgba(41,37,36,0.06),0_14px_40px_rgba(41,37,36,0.06)] outline-none transition-transform hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-emerald-600 dark:bg-stone-900 dark:shadow-none">
                            <div class="flex items-start justify-between gap-4">
                                <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-lg font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">
                                    {{ mb_strtoupper(mb_substr($vault->name, 0, 1)) }}
                                </span>
                                <span class="text-xs font-medium tabular-nums text-stone-400 dark:text-stone-500">{{ $vault->active_authenticators_count }} {{ Str::plural('code', $vault->active_authenticators_count) }}</span>
                            </div>
                            <h3 class="mt-8 text-xl font-semibold tracking-tight text-stone-950 dark:text-white">{{ $vault->name }}</h3>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Open vault <span aria-hidden="true">→</span></p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layouts.app>

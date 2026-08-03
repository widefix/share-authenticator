<x-layouts.app title="Vault settings">
    <header class="flex flex-col gap-6 border-b border-stone-200 pb-8 sm:flex-row sm:items-end sm:justify-between dark:border-stone-800">
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-stone-500 underline-offset-4 hover:text-stone-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-400 dark:hover:text-white">← Back to dashboard</a>
            <h1 class="mt-4 text-4xl font-semibold tracking-[-0.035em] text-stone-950 dark:text-white">Vault settings</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-stone-500 dark:text-stone-400">Create and organize the vaults in {{ $workspace->name }}.</p>
        </div>
        <p class="text-sm tabular-nums text-stone-500 dark:text-stone-400">{{ $activeVaults->count() }} active · {{ $archivedVaults->count() }} archived</p>
    </header>

    @if (session('status'))
        <div class="mt-7 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-7 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert">
            <p class="font-semibold">We couldn’t save that change.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-10 grid gap-8 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start" aria-labelledby="create-vault-title">
        <div class="rounded-2xl bg-stone-900 p-6 text-white dark:bg-stone-100 dark:text-stone-950">
            <p class="text-xs font-semibold tracking-[0.14em] text-stone-400 uppercase dark:text-stone-500">New vault</p>
            <h2 id="create-vault-title" class="mt-3 text-2xl font-semibold tracking-tight">Create a vault</h2>
            <p class="mt-2 text-sm leading-6 text-stone-300 dark:text-stone-600">Group codes by team, client, or responsibility.</p>

            <form method="POST" action="{{ route('vaults.store') }}" class="mt-6 space-y-4" data-submitting-form>
                @csrf
                <div>
                    <label for="new-vault-name" class="block text-sm font-medium">Vault name</label>
                    <input id="new-vault-name" name="name" type="text" value="{{ old('name') }}" maxlength="80" required class="mt-2 block h-11 w-full rounded-xl border border-stone-600 bg-stone-800 px-3.5 text-sm text-white outline-none placeholder:text-stone-500 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 dark:border-stone-300 dark:bg-white dark:text-stone-950" placeholder="Engineering">
                </div>
                <button type="submit" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-stone-950 outline-none transition-colors hover:bg-emerald-400 focus-visible:ring-2 focus-visible:ring-emerald-300 active:translate-y-px disabled:cursor-wait disabled:opacity-70" data-submit-button data-loading-label="Creating...">Create vault</button>
            </form>
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="text-xl font-semibold tracking-tight text-stone-950 dark:text-white">Active vaults</h2>
                <p class="text-xs text-stone-500 dark:text-stone-400">Names are unique in this workspace</p>
            </div>

            <div class="mt-4 divide-y divide-stone-200 overflow-hidden rounded-2xl border border-stone-200 bg-white dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900">
                @foreach ($activeVaults as $vault)
                    <article class="p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-5">
                            <div>
                                <a href="{{ route('vaults.authenticators.index', $vault) }}" class="font-semibold text-stone-950 underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-white">{{ $vault->name }}</a>
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $vault->authenticators_count }} authenticators · {{ $workspace->users_count }} {{ Str::plural('workspace member', $workspace->users_count) }}</p>
                            </div>
                            <span class="mt-1 size-2 rounded-full bg-emerald-500" aria-label="Active"></span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <form method="POST" action="{{ route('vaults.update', $vault) }}" class="flex gap-2" data-submitting-form>
                                @csrf
                                @method('PATCH')
                                <label for="vault-name-{{ $vault->id }}" class="sr-only">Rename {{ $vault->name }}</label>
                                <input id="vault-name-{{ $vault->id }}" name="name" type="text" value="{{ $vault->name }}" maxlength="80" required class="h-10 min-w-0 flex-1 rounded-lg border border-stone-300 bg-white px-3 text-sm text-stone-950 outline-none focus:border-emerald-600 focus:ring-3 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-950 dark:text-white">
                                <button type="submit" class="h-10 rounded-lg border border-stone-300 px-3 text-sm font-semibold text-stone-700 transition-colors hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:opacity-60 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800" data-submit-button data-loading-label="Saving...">Save</button>
                            </form>

                            <form method="POST" action="{{ route('vaults.archive', $vault) }}" data-submitting-form>
                                @csrf
                                <button type="submit" @disabled($activeVaults->count() === 1) class="h-10 rounded-lg px-3 text-sm font-medium text-stone-500 transition-colors hover:bg-stone-100 hover:text-stone-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:cursor-not-allowed disabled:opacity-40 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-white" data-submit-button data-loading-label="Archiving...">Archive</button>
                            </form>
                        </div>

                        <details class="mt-4 border-t border-stone-100 pt-4 dark:border-stone-800">
                            <summary class="cursor-pointer text-sm font-medium text-red-700 outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:text-red-400">Delete vault</summary>
                            <form method="POST" action="{{ route('vaults.destroy', $vault) }}" class="mt-4 rounded-xl bg-red-50 p-4 dark:bg-red-950/50" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <p class="text-sm leading-6 text-red-900 dark:text-red-100">This permanently removes {{ $vault->authenticators_count }} authenticators and affects {{ $workspace->users_count }} {{ Str::plural('workspace member', $workspace->users_count) }}. Type <strong>{{ $vault->name }}</strong> to confirm.</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                    <label for="delete-vault-{{ $vault->id }}" class="sr-only">Type {{ $vault->name }} to confirm deletion</label>
                                    <input id="delete-vault-{{ $vault->id }}" name="confirmation" type="text" required autocomplete="off" class="h-10 min-w-0 flex-1 rounded-lg border border-red-300 bg-white px-3 text-sm text-stone-950 outline-none focus:border-red-600 focus:ring-3 focus:ring-red-600/15 dark:border-red-800 dark:bg-stone-950 dark:text-white">
                                    <button type="submit" @disabled($activeVaults->count() === 1) class="h-10 rounded-lg bg-red-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:cursor-not-allowed disabled:opacity-40" data-submit-button data-loading-label="Deleting...">Delete permanently</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($archivedVaults->isNotEmpty())
        <section class="mt-14 border-t border-stone-200 pt-9 dark:border-stone-800" aria-labelledby="archived-vaults-title">
            <h2 id="archived-vaults-title" class="text-xl font-semibold tracking-tight text-stone-950 dark:text-white">Archived vaults</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Archived vaults stay out of the dashboard until restored.</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($archivedVaults as $vault)
                    <article class="rounded-xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-stone-900 dark:text-stone-100">{{ $vault->name }}</h3>
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $vault->authenticators_count }} authenticators · Archived {{ $vault->archived_at->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('vaults.restore', $vault) }}" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-emerald-400 dark:hover:bg-emerald-950" data-submit-button data-loading-label="Restoring...">Restore</button>
                            </form>
                        </div>

                        <details class="mt-4 border-t border-stone-100 pt-4 dark:border-stone-800">
                            <summary class="cursor-pointer text-sm font-medium text-red-700 outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:text-red-400">Delete vault</summary>
                            <form method="POST" action="{{ route('vaults.destroy', $vault) }}" class="mt-4 rounded-xl bg-red-50 p-4 dark:bg-red-950/50" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <p class="text-sm leading-6 text-red-900 dark:text-red-100">Type <strong>{{ $vault->name }}</strong> to permanently delete this archived vault.</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                    <label for="delete-archived-vault-{{ $vault->id }}" class="sr-only">Type {{ $vault->name }} to confirm deletion</label>
                                    <input id="delete-archived-vault-{{ $vault->id }}" name="confirmation" type="text" required autocomplete="off" class="h-10 min-w-0 flex-1 rounded-lg border border-red-300 bg-white px-3 text-sm text-stone-950 outline-none focus:border-red-600 focus:ring-3 focus:ring-red-600/15 dark:border-red-800 dark:bg-stone-950 dark:text-white">
                                    <button type="submit" class="h-10 rounded-lg bg-red-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:cursor-wait disabled:opacity-60" data-submit-button data-loading-label="Deleting...">Delete permanently</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>

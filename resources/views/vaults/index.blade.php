<x-layouts.app title="Vault settings">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="link-quiet"><span aria-hidden="true">←</span> Vaults</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <span class="text-ink-secondary">Settings</span>
    </nav>

    <header class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="eyebrow">{{ $workspace->name }}</p>
            <h1 class="display-xl mt-3 text-ink">Vault settings</h1>
            <p class="body-lead mt-3 max-w-xl">Create and organize the vaults in {{ $workspace->name }}.</p>
        </div>
        <p class="caption whitespace-nowrap">
            {{ $activeVaults->count() }} active
            <span aria-hidden="true" class="mx-1 text-hairline-input">·</span>
            {{ $archivedVaults->count() }} archived
        </p>
    </header>

    @if (session('status'))
        <div class="alert alert-success mt-7" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error mt-7" role="alert">
            <div>
                <p class="font-semibold">We couldn't save that change.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="mt-9 grid gap-8 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start" aria-labelledby="create-vault-title">
        <div class="card card-raised p-6 lg:sticky lg:top-24">
            <p class="eyebrow">New vault</p>
            <h2 id="create-vault-title" class="display-md mt-3 text-ink">Create a vault</h2>
            <p class="body-mute mt-2">Group codes by team, client, or responsibility.</p>

            <form method="POST" action="{{ route('vaults.store') }}" class="mt-6 space-y-4" data-submitting-form>
                @csrf
                <div class="field">
                    <label for="new-vault-name" class="field-label">Vault name</label>
                    <input id="new-vault-name" name="name" type="text" value="{{ old('name') }}" maxlength="80" required class="input mt-2" placeholder="Engineering">
                </div>
                <button type="submit" class="btn btn-primary btn-block" data-submit-button data-loading-label="Creating...">Create vault</button>
            </form>
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="heading-md text-ink">Active vaults</h2>
                <p class="caption">Names are unique in this workspace</p>
            </div>

            <div class="card mt-4 divide-y divide-hairline overflow-hidden">
                @foreach ($activeVaults as $vault)
                    <article class="p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-5">
                            <div class="min-w-0">
                                <a href="{{ route('vaults.authenticators.index', $vault) }}" class="link heading-sm">{{ $vault->name }}</a>
                                <p class="caption mt-1">{{ $vault->authenticators_count }} authenticators · {{ $vault->accessible_members_count }} {{ Str::plural('workspace member', $vault->accessible_members_count) }} with access</p>
                            </div>
                            <span class="pill pill-indigo">Active</span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <form method="POST" action="{{ route('vaults.update', $vault) }}" class="flex gap-2" data-submitting-form>
                                @csrf
                                @method('PATCH')
                                <label for="vault-name-{{ $vault->id }}" class="sr-only">Rename {{ $vault->name }}</label>
                                <input id="vault-name-{{ $vault->id }}" name="name" type="text" value="{{ $vault->name }}" maxlength="80" required class="input min-w-0 flex-1">
                                <button type="submit" class="btn btn-neutral btn-sm" data-submit-button data-loading-label="Saving...">Save</button>
                            </form>

                            <form method="POST" action="{{ route('vaults.archive', $vault) }}" data-submitting-form>
                                @csrf
                                <button type="submit" @disabled($activeVaults->count() === 1) class="btn btn-ghost btn-sm" data-submit-button data-loading-label="Archiving...">Archive</button>
                            </form>
                        </div>

                        <details class="mt-4 border-t border-hairline pt-4">
                            <summary class="cursor-pointer text-sm font-medium text-ruby-deep">Delete vault</summary>
                            <form method="POST" action="{{ route('vaults.destroy', $vault) }}" class="alert-danger-zone mt-4" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <p class="text-sm leading-6 text-ruby-deep">This permanently removes {{ $vault->authenticators_count }} authenticators and affects {{ $vault->accessible_members_count }} {{ Str::plural('member', $vault->accessible_members_count) }} with access. Type <strong>{{ $vault->name }}</strong> to confirm.</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                    <label for="delete-vault-{{ $vault->id }}" class="sr-only">Type {{ $vault->name }} to confirm deletion</label>
                                    <input id="delete-vault-{{ $vault->id }}" name="confirmation" type="text" required autocomplete="off" class="input min-w-0 flex-1">
                                    <button type="submit" @disabled($activeVaults->count() === 1) class="btn btn-danger btn-sm" data-submit-button data-loading-label="Deleting...">Delete permanently</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($archivedVaults->isNotEmpty())
        <section class="mt-12 border-t border-hairline pt-8" aria-labelledby="archived-vaults-title">
            <h2 id="archived-vaults-title" class="heading-md text-ink">Archived vaults</h2>
            <p class="body-mute mt-1">Archived vaults stay out of the dashboard until restored.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($archivedVaults as $vault)
                    <article class="card p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="heading-sm text-ink">{{ $vault->name }}</h3>
                                <p class="caption mt-1">{{ $vault->authenticators_count }} authenticators · Archived {{ $vault->archived_at->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('vaults.restore', $vault) }}" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" data-submit-button data-loading-label="Restoring...">Restore</button>
                            </form>
                        </div>

                        <details class="mt-4 border-t border-hairline pt-4">
                            <summary class="cursor-pointer text-sm font-medium text-ruby-deep">Delete vault</summary>
                            <form method="POST" action="{{ route('vaults.destroy', $vault) }}" class="alert-danger-zone mt-4" data-submitting-form>
                                @csrf
                                @method('DELETE')
                                <p class="text-sm leading-6 text-ruby-deep">Type <strong>{{ $vault->name }}</strong> to permanently delete this archived vault.</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                    <label for="delete-archived-vault-{{ $vault->id }}" class="sr-only">Type {{ $vault->name }} to confirm deletion</label>
                                    <input id="delete-archived-vault-{{ $vault->id }}" name="confirmation" type="text" required autocomplete="off" class="input min-w-0 flex-1">
                                    <button type="submit" class="btn btn-danger btn-sm" data-submit-button data-loading-label="Deleting...">Delete permanently</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>

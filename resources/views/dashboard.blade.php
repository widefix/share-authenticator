<x-layouts.app title="Dashboard">
    @if (session('status'))
        <div class="alert alert-success mb-8" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (! $user->workspace)
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_25rem] lg:gap-16">
            <section class="pt-2">
                <p class="eyebrow">First things first</p>
                <h1 class="display-xxl mt-4 max-w-2xl text-ink">Create a home for your team's sign-in codes.</h1>
                <p class="body-lead mt-5 max-w-xl">
                    A workspace keeps your vaults together. We'll start it with a General vault, and you can organize more as your team grows.
                </p>

                <dl class="mt-10 grid max-w-xl gap-5 sm:grid-cols-2">
                    <div class="card p-5">
                        <dt class="heading-sm text-ink">One workspace</dt>
                        <dd class="body-mute mt-1.5">Your account belongs to one team workspace in this version.</dd>
                    </div>
                    <div class="card p-5">
                        <dt class="heading-sm text-ink">General included</dt>
                        <dd class="body-mute mt-1.5">The first vault is created automatically and can be renamed later.</dd>
                    </div>
                </dl>
            </section>

            <section class="card card-raised p-6 sm:p-8" aria-labelledby="workspace-form-title">
                <p class="eyebrow">Workspace setup</p>
                <h2 id="workspace-form-title" class="display-md mt-3 text-ink">Name your workspace</h2>
                <p class="body-mute mt-2">Use your team, company, or project name.</p>

                <form method="POST" action="{{ route('workspaces.store') }}" class="mt-7 space-y-5" data-submitting-form>
                    @csrf

                    <div class="field">
                        <label for="workspace-name" class="field-label">Workspace name</label>
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
                            class="input mt-2 h-11 {{ $errors->has('name') ? 'input-invalid' : '' }}"
                            placeholder="Northstar Studio"
                        >
                        @error('name')
                            <p id="workspace-name-error" class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg btn-block"
                        data-submit-button
                        data-loading-label="Creating workspace..."
                    >
                        Create workspace
                    </button>
                </form>
            </section>
        </div>
    @else
        <header class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="eyebrow">{{ $user->workspace->name }}</p>
                <h1 class="display-xl mt-3 text-ink">Your vaults</h1>
                <p class="body-lead mt-3 max-w-xl">Vaults group the authenticator codes your team needs together.</p>
            </div>

            @if ($user->isWorkspaceAdmin())
                <a href="{{ route('vaults.index') }}" class="btn btn-secondary self-start sm:self-auto">
                    Manage vaults
                </a>
            @endif
        </header>

        <dl class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="card px-5 py-4">
                <dt class="caption">Active vaults</dt>
                <dd class="display-md tabular mt-1 text-ink">{{ $vaults->count() }}</dd>
            </div>
            <div class="card px-5 py-4">
                <dt class="caption">Codes available</dt>
                <dd class="display-md tabular mt-1 text-ink">{{ $vaults->sum('active_authenticators_count') }}</dd>
            </div>
            <div class="card px-5 py-4">
                <dt class="caption">Your role</dt>
                <dd class="mt-2"><span class="pill pill-indigo">{{ $user->isWorkspaceAdmin() ? 'Admin' : 'Member' }}</span></dd>
            </div>
        </dl>

        <section class="mt-10" aria-labelledby="active-vaults-title">
            <h2 id="active-vaults-title" class="heading-md text-ink">Vaults</h2>

            @if ($vaults->isEmpty())
                <div class="panel-empty mt-4">
                    <p class="heading-sm text-ink">No vaults available</p>
                    <p class="body-mute mt-2">Ask a workspace admin to grant access.</p>
                </div>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vaults as $vault)
                        <a href="{{ route('vaults.authenticators.index', $vault) }}" class="card card-interactive group flex flex-col p-5">
                            <div class="flex items-start justify-between gap-4">
                                <span class="grid size-10 place-items-center rounded-lg bg-primary-wash text-base font-medium text-primary-deep" aria-hidden="true">
                                    {{ mb_strtoupper(mb_substr($vault->name, 0, 1)) }}
                                </span>
                                <span class="pill pill-neutral tabular">{{ $vault->active_authenticators_count }} {{ Str::plural('code', $vault->active_authenticators_count) }}</span>
                            </div>
                            <h3 class="heading-lg mt-7 text-ink">{{ $vault->name }}</h3>
                            <p class="mt-1 text-sm font-medium text-primary">
                                Open vault <span aria-hidden="true" class="inline-block transition-transform group-hover:translate-x-0.5">→</span>
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layouts.app>

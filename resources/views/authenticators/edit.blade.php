<x-layouts.app title="Edit authenticator">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="link-quiet">Vaults</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <a href="{{ route('vaults.authenticators.index', $authenticator->vault) }}" class="link-quiet">{{ $authenticator->vault->name }}</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <span class="text-ink-secondary">Edit</span>
    </nav>

    <header class="mt-4 max-w-3xl">
        <p class="eyebrow">Authenticator</p>
        <h1 class="display-xl mt-3 text-ink">Edit authenticator</h1>
        <p class="body-lead mt-3">The saved seed remains encrypted and cannot be displayed.</p>
    </header>

    @if ($errors->any())
        <div class="alert alert-error mt-7 max-w-3xl" role="alert">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('authenticators.update', $authenticator) }}" class="mt-8 max-w-3xl space-y-6" data-authenticator-form>
        @csrf
        @method('PATCH')

        <section class="card grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <div class="field">
                <label for="issuer" class="field-label">Issuer</label>
                <input id="issuer" name="issuer" value="{{ old('issuer', $data->issuer) }}" required maxlength="120" class="input mt-2">
            </div>
            <div class="field">
                <label for="account" class="field-label">Account</label>
                <input id="account" name="account" value="{{ old('account', $data->account) }}" required maxlength="120" class="input mt-2">
            </div>
            <div class="field sm:col-span-2">
                <label for="vault_id" class="field-label">Vault</label>
                <select id="vault_id" name="vault_id" class="select mt-2">
                    @foreach ($vaults as $vault)
                        <option value="{{ $vault->id }}" @selected(old('vault_id', $authenticator->vault_id) == $vault->id)>{{ $vault->name }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <details class="card p-5 sm:p-6">
            <summary class="heading-sm cursor-pointer text-ink">Replace secret or TOTP settings</summary>
            <p class="body-mute mt-3">Only use this after rotating the authenticator at the originating service.</p>

            <div class="mt-5 space-y-4">
                <div class="field">
                    <label for="replacement_uri" class="field-label">New otpauth URI</label>
                    <textarea id="replacement_uri" name="replacement_uri" rows="3" autocomplete="off" spellcheck="false" class="textarea input-mono mt-2"></textarea>
                </div>

                <p class="eyebrow text-center">or manual replacement</p>

                <div class="field">
                    <label for="replacement_secret" class="field-label">New Base32 secret</label>
                    <input id="replacement_secret" name="replacement_secret" autocomplete="off" spellcheck="false" class="input input-mono mt-2">
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <select name="replacement_algorithm" aria-label="New algorithm" class="select">
                        <option>SHA1</option>
                        <option>SHA256</option>
                        <option>SHA512</option>
                    </select>
                    <select name="replacement_digits" aria-label="New digits" class="select">
                        <option value="6">6 digits</option>
                        <option value="8">8 digits</option>
                    </select>
                    <input name="replacement_period" type="number" min="15" max="300" value="30" aria-label="New period" class="input tabular">
                </div>

                <label class="alert alert-warning">
                    <input type="checkbox" name="confirm_sensitive_change" value="1" class="checkbox mt-0.5">
                    <span>I updated the provider and understand the previous codes will stop working.</span>
                </label>
            </div>
        </details>

        <label class="alert alert-warning hidden" data-duplicate-confirm>
            <input type="checkbox" name="confirm_duplicate" value="1" class="checkbox mt-0.5">
            <span>I understand this configuration already exists and want another copy.</span>
        </label>

        <div class="alert alert-error hidden" role="alert" data-form-errors></div>

        <button type="submit" class="btn btn-primary" data-submit-button data-loading-label="Saving…">Save changes</button>
    </form>

    <section class="mt-12 max-w-3xl border-t border-hairline pt-8">
        <h2 class="heading-md text-ink">Archive authenticator</h2>
        <p class="body-mute mt-2">Archived authenticators stop generating codes and can be restored later.</p>
        <form method="POST" action="{{ route('authenticators.archive', $authenticator) }}" class="mt-4" data-submitting-form>
            @csrf
            <button type="submit" class="btn btn-neutral btn-sm" data-submit-button data-loading-label="Archiving…">Archive</button>
        </form>
    </section>
</x-layouts.app>

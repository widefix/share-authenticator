<x-layouts.app title="Edit authenticator">
    <header class="max-w-3xl">
        <a href="{{ route('vaults.authenticators.index', $authenticator->vault) }}" class="text-sm font-medium text-stone-500 underline-offset-4 hover:text-stone-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-400 dark:hover:text-white">← Back to {{ $authenticator->vault->name }}</a>
        <h1 class="mt-5 text-4xl font-semibold tracking-[-0.035em] text-stone-950 dark:text-white">Edit authenticator</h1>
        <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">The saved seed remains encrypted and cannot be displayed.</p>
    </header>

    @if ($errors->any())<div class="mt-7 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('authenticators.update', $authenticator) }}" class="mt-9 max-w-3xl space-y-8" data-authenticator-form>
        @csrf @method('PATCH')
        <section class="grid gap-5 rounded-2xl bg-white p-6 sm:grid-cols-2 dark:bg-stone-900">
            <div><label for="issuer" class="block text-sm font-medium">Issuer</label><input id="issuer" name="issuer" value="{{ old('issuer', $data->issuer) }}" required maxlength="120" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 dark:border-stone-700 dark:bg-stone-950"></div>
            <div><label for="account" class="block text-sm font-medium">Account</label><input id="account" name="account" value="{{ old('account', $data->account) }}" required maxlength="120" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 dark:border-stone-700 dark:bg-stone-950"></div>
            <div class="sm:col-span-2"><label for="vault_id" class="block text-sm font-medium">Vault</label><select id="vault_id" name="vault_id" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 dark:border-stone-700 dark:bg-stone-950">@foreach ($vaults as $vault)<option value="{{ $vault->id }}" @selected(old('vault_id', $authenticator->vault_id) == $vault->id)>{{ $vault->name }}</option>@endforeach</select></div>
        </section>

        <details class="rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <summary class="cursor-pointer font-semibold text-stone-900 outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-100">Replace secret or TOTP settings</summary>
            <p class="mt-3 text-sm leading-6 text-stone-500 dark:text-stone-400">Only use this after rotating the authenticator at the originating service.</p>
            <div class="mt-5 space-y-4">
                <div><label for="replacement_uri" class="block text-sm font-medium">New otpauth URI</label><textarea id="replacement_uri" name="replacement_uri" rows="3" autocomplete="off" spellcheck="false" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-3.5 py-3 font-mono text-sm dark:border-stone-700 dark:bg-stone-950"></textarea></div>
                <p class="text-center text-xs font-semibold tracking-[0.12em] text-stone-400 uppercase">or manual replacement</p>
                <div><label for="replacement_secret" class="block text-sm font-medium">New Base32 secret</label><input id="replacement_secret" name="replacement_secret" autocomplete="off" spellcheck="false" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 font-mono dark:border-stone-700 dark:bg-stone-950"></div>
                <div class="grid gap-4 sm:grid-cols-3"><select name="replacement_algorithm" aria-label="New algorithm" class="h-11 rounded-xl border border-stone-300 bg-white px-3 dark:border-stone-700 dark:bg-stone-950"><option>SHA1</option><option>SHA256</option><option>SHA512</option></select><select name="replacement_digits" aria-label="New digits" class="h-11 rounded-xl border border-stone-300 bg-white px-3 dark:border-stone-700 dark:bg-stone-950"><option value="6">6 digits</option><option value="8">8 digits</option></select><input name="replacement_period" type="number" min="15" max="300" value="30" aria-label="New period" class="h-11 rounded-xl border border-stone-300 bg-white px-3 dark:border-stone-700 dark:bg-stone-950"></div>
                <label class="block rounded-xl bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100"><input type="checkbox" name="confirm_sensitive_change" value="1" class="mr-2">I updated the provider and understand the previous codes will stop working.</label>
            </div>
        </details>

        <label class="hidden rounded-xl bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100" data-duplicate-confirm><input type="checkbox" name="confirm_duplicate" value="1" class="mr-2">I understand this configuration already exists and want another copy.</label>
        <div class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert" data-form-errors></div>
        <button type="submit" class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:opacity-60" data-submit-button data-loading-label="Saving…">Save changes</button>
    </form>

    <section class="mt-14 max-w-3xl border-t border-stone-200 pt-8 dark:border-stone-800">
        <h2 class="text-xl font-semibold text-stone-950 dark:text-white">Archive authenticator</h2>
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Archived authenticators stop generating codes and can be restored later.</p>
        <form method="POST" action="{{ route('authenticators.archive', $authenticator) }}" class="mt-4" data-submitting-form>@csrf<button type="submit" class="h-10 rounded-lg border border-stone-300 px-4 text-sm font-semibold hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800" data-submit-button data-loading-label="Archiving…">Archive</button></form>
    </section>
</x-layouts.app>

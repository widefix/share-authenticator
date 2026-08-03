<x-layouts.app title="Add authenticator">
    <header class="max-w-3xl">
        <a href="{{ route('vaults.authenticators.index', $vault) }}" class="text-sm font-medium text-stone-500 underline-offset-4 hover:text-stone-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-400 dark:hover:text-white">← Back to {{ $vault->name }}</a>
        <p class="mt-6 text-sm font-semibold text-emerald-700 dark:text-emerald-400">New authenticator</p>
        <h1 class="mt-2 text-balance text-4xl font-semibold tracking-[-0.035em] text-stone-950 sm:text-5xl dark:text-white">Bring in a setup code.</h1>
        <p class="mt-4 max-w-2xl text-pretty text-base leading-7 text-stone-600 dark:text-stone-300">Scan the provider’s QR code, choose a saved image, or enter the details manually. Images stay on this device.</p>
    </header>

    <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start" data-authenticator-intake>
        <section class="rounded-2xl bg-white p-5 shadow-[0_18px_60px_rgba(41,37,36,0.07)] sm:p-7 dark:bg-stone-900 dark:shadow-none">
            <div class="grid grid-cols-3 gap-1 rounded-xl bg-stone-100 p-1 dark:bg-stone-950" role="tablist" aria-label="Import method">
                <button type="button" class="rounded-lg px-2 py-2.5 text-sm font-semibold outline-none focus-visible:ring-2 focus-visible:ring-emerald-600" data-intake-tab="camera">Camera</button>
                <button type="button" class="rounded-lg px-2 py-2.5 text-sm font-semibold outline-none focus-visible:ring-2 focus-visible:ring-emerald-600" data-intake-tab="image">Image</button>
                <button type="button" class="rounded-lg px-2 py-2.5 text-sm font-semibold outline-none focus-visible:ring-2 focus-visible:ring-emerald-600" data-intake-tab="manual">Manual</button>
            </div>

            <div class="mt-6" data-intake-panel="camera">
                <div class="grid min-h-72 place-items-center overflow-hidden rounded-xl bg-stone-950 text-center text-stone-300">
                    <video class="hidden size-full max-h-96 object-cover" playsinline muted data-qr-video></video>
                    <div class="max-w-xs p-8" data-camera-placeholder><p class="font-semibold text-white">Camera is off</p><p class="mt-2 text-sm leading-6 text-stone-400">Permission is requested only after you start scanning.</p></div>
                </div>
                <div class="mt-4 flex gap-3"><button type="button" class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600" data-camera-start>Start scanning</button><button type="button" class="hidden h-11 rounded-xl px-4 text-sm font-semibold text-stone-600 hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-stone-300 dark:hover:bg-stone-800" data-camera-stop>Cancel</button></div>
            </div>

            <div class="mt-6 hidden" data-intake-panel="image">
                <label for="qr-image" class="grid min-h-72 cursor-pointer place-items-center rounded-xl border border-dashed border-stone-300 px-6 text-center transition-colors hover:border-emerald-600 hover:bg-emerald-50/40 dark:border-stone-700 dark:hover:bg-emerald-950/20" data-image-drop>
                    <span><strong class="block text-stone-900 dark:text-stone-100">Choose or drop a QR image</strong><span class="mt-2 block text-sm leading-6 text-stone-500 dark:text-stone-400">PNG, JPEG, or WebP. You can also paste an image.</span></span>
                </label>
                <input id="qr-image" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" data-qr-image>
            </div>

            <div class="mt-6 hidden" data-intake-panel="manual">
                <div>
                    <label for="authenticator-uri" class="block text-sm font-medium text-stone-800 dark:text-stone-200">otpauth URI</label>
                    <textarea id="authenticator-uri" rows="4" autocomplete="off" spellcheck="false" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-3.5 py-3 font-mono text-sm text-stone-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-950 dark:text-white" placeholder="otpauth://totp/…" data-uri-input></textarea>
                    <button type="button" class="mt-3 h-10 rounded-lg border border-stone-300 px-4 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800" data-parse-uri>Review URI</button>
                </div>
                <p class="my-6 text-center text-xs font-semibold tracking-[0.12em] text-stone-400 uppercase">or enter fields</p>
                <button type="button" class="h-10 rounded-lg bg-stone-900 px-4 text-sm font-semibold text-white hover:bg-stone-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:bg-stone-100 dark:text-stone-950" data-manual-review>Enter details</button>
            </div>

            <div class="mt-5 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert" data-intake-error></div>
        </section>

        <aside class="rounded-2xl bg-stone-900 p-6 text-white dark:bg-stone-100 dark:text-stone-950">
            <p class="text-xs font-semibold tracking-[0.14em] text-stone-400 uppercase dark:text-stone-500">Private by design</p>
            <h2 class="mt-3 text-xl font-semibold">The seed is shown once.</h2>
            <p class="mt-3 text-sm leading-6 text-stone-300 dark:text-stone-600">After saving, you can reveal and copy rotating codes, but the original seed cannot be viewed or exported.</p>
        </aside>

        <section class="hidden lg:col-span-2" data-authenticator-review>
            <div class="max-w-3xl border-t border-stone-200 pt-9 dark:border-stone-800">
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Review before saving</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-stone-950 dark:text-white">Authenticator details</h2>
                <form method="POST" action="{{ route('vaults.authenticators.store', $vault) }}" class="mt-7 grid gap-5 sm:grid-cols-2" data-authenticator-form>
                    @csrf
                    <div class="sm:col-span-2"><label for="secret" class="block text-sm font-medium">Base32 secret</label><input id="secret" name="secret" required maxlength="256" autocomplete="off" spellcheck="false" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 font-mono text-sm tracking-wide outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-900" data-review-secret><p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Visible only during this review. It cannot be recovered after saving.</p></div>
                    <div><label for="issuer" class="block text-sm font-medium">Issuer</label><input id="issuer" name="issuer" required maxlength="120" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-900" data-review-issuer></div>
                    <div><label for="account" class="block text-sm font-medium">Account</label><input id="account" name="account" required maxlength="120" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 dark:border-stone-700 dark:bg-stone-900" data-review-account></div>
                    <div><label for="algorithm" class="block text-sm font-medium">Algorithm</label><select id="algorithm" name="algorithm" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3.5 dark:border-stone-700 dark:bg-stone-900" data-review-algorithm><option>SHA1</option><option>SHA256</option><option>SHA512</option></select></div>
                    <div class="grid grid-cols-2 gap-3"><div><label for="digits" class="block text-sm font-medium">Digits</label><select id="digits" name="digits" class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3 dark:border-stone-700 dark:bg-stone-900" data-review-digits><option value="6">6</option><option value="8">8</option></select></div><div><label for="period" class="block text-sm font-medium">Period</label><input id="period" name="period" type="number" min="15" max="300" value="30" required class="mt-2 h-11 w-full rounded-xl border border-stone-300 bg-white px-3 dark:border-stone-700 dark:bg-stone-900" data-review-period></div></div>
                    <div><label for="vault_id_display" class="block text-sm font-medium">Vault</label><input id="vault_id_display" value="{{ $vault->name }}" disabled class="mt-2 h-11 w-full rounded-xl border border-stone-200 bg-stone-100 px-3.5 text-stone-500 dark:border-stone-800 dark:bg-stone-950"></div>
                    <div class="flex items-end"><button type="submit" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:opacity-60" data-submit-button data-loading-label="Encrypting and saving…">Save authenticator</button></div>
                    <label class="hidden sm:col-span-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100" data-duplicate-confirm><input type="checkbox" name="confirm_duplicate" value="1" class="mr-2">I understand this configuration already exists and want another copy.</label>
                    <div class="hidden sm:col-span-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100" role="alert" data-form-errors></div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>

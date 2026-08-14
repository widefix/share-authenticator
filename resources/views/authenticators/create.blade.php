<x-layouts.app title="Add authenticator">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="link-quiet">Vaults</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <a href="{{ route('vaults.authenticators.index', $vault) }}" class="link-quiet">{{ $vault->name }}</a>
        <span aria-hidden="true" class="text-hairline-input">/</span>
        <span class="text-ink-secondary">Add</span>
    </nav>

    <header class="mt-4 max-w-3xl">
        <p class="eyebrow">New authenticator</p>
        <h1 class="display-xl mt-3 text-ink">Bring in a setup code.</h1>
        <p class="body-lead mt-4 max-w-2xl">Scan the provider's QR code, choose a saved image, or enter the details manually. Images stay on this device.</p>
    </header>

    <div class="mt-9 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start" data-authenticator-intake>
        <section class="card card-raised p-5 sm:p-6">
            <div class="segmented grid-cols-3" role="tablist" aria-label="Import method">
                <button type="button" role="tab" aria-controls="intake-panel-camera" class="segment" data-intake-tab="camera">Camera</button>
                <button type="button" role="tab" aria-controls="intake-panel-image" class="segment" data-intake-tab="image">Image</button>
                <button type="button" role="tab" aria-controls="intake-panel-manual" class="segment" data-intake-tab="manual">Manual</button>
            </div>

            <div id="intake-panel-camera" role="tabpanel" class="mt-6" data-intake-panel="camera">
                <div class="grid min-h-72 place-items-center overflow-hidden rounded-xl bg-brand-dark text-center">
                    <video class="hidden size-full max-h-96 object-cover" playsinline muted data-qr-video></video>
                    <div class="max-w-xs p-8" data-camera-placeholder>
                        <p class="heading-sm text-white">Camera is off</p>
                        <p class="mt-2 text-sm leading-6 text-white/70">Permission is requested only after you start scanning.</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="button" class="btn btn-primary" data-camera-start>Start scanning</button>
                    <button type="button" class="btn btn-ghost hidden" data-camera-stop>Cancel</button>
                </div>
            </div>

            <div id="intake-panel-image" role="tabpanel" class="mt-6 hidden" data-intake-panel="image">
                <label for="qr-image" class="grid min-h-72 cursor-pointer place-items-center rounded-xl border border-dashed border-hairline-input px-6 text-center transition-colors hover:border-primary hover:bg-primary-wash" data-image-drop>
                    <span>
                        <strong class="heading-sm block text-ink">Choose or drop a QR image</strong>
                        <span class="body-mute mt-2 block">PNG, JPEG, or WebP. You can also paste an image.</span>
                    </span>
                </label>
                <input id="qr-image" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" data-qr-image>
            </div>

            <div id="intake-panel-manual" role="tabpanel" class="mt-6 hidden" data-intake-panel="manual">
                <div class="field">
                    <label for="authenticator-uri" class="field-label">otpauth URI</label>
                    <textarea id="authenticator-uri" rows="4" autocomplete="off" spellcheck="false" class="textarea input-mono mt-2" placeholder="otpauth://totp/…" data-uri-input></textarea>
                    <button type="button" class="btn btn-neutral btn-sm mt-3" data-parse-uri>Review URI</button>
                </div>
                <p class="eyebrow my-6 text-center">or enter fields</p>
                <button type="button" class="btn btn-secondary btn-sm" data-manual-review>Enter details</button>
            </div>

            <div class="alert alert-error mt-5 hidden" role="alert" data-intake-error></div>
        </section>

        <aside class="card-cream p-6">
            <p class="eyebrow text-lemon">Private by design</p>
            <h2 class="heading-lg mt-3 text-ink">The seed is shown once.</h2>
            <p class="body-text mt-3">After saving, you can reveal and copy rotating codes, but the original seed cannot be viewed or exported.</p>
        </aside>

        <section class="hidden lg:col-span-2" data-authenticator-review>
            <div class="max-w-3xl border-t border-hairline pt-8">
                <p class="eyebrow">Review before saving</p>
                <h2 class="display-lg mt-3 text-ink">Authenticator details</h2>

                <form method="POST" action="{{ route('vaults.authenticators.store', $vault) }}" class="card mt-6 grid gap-5 p-5 sm:grid-cols-2 sm:p-6" data-authenticator-form>
                    @csrf

                    <div class="field sm:col-span-2">
                        <label for="secret" class="field-label">Base32 secret</label>
                        <input id="secret" name="secret" required maxlength="256" autocomplete="off" spellcheck="false" class="input input-mono mt-2" data-review-secret>
                        <p class="field-hint">Visible only during this review. It cannot be recovered after saving.</p>
                    </div>

                    <div class="field">
                        <label for="issuer" class="field-label">Issuer</label>
                        <input id="issuer" name="issuer" required maxlength="120" class="input mt-2" data-review-issuer>
                    </div>

                    <div class="field">
                        <label for="account" class="field-label">Account</label>
                        <input id="account" name="account" required maxlength="120" class="input mt-2" data-review-account>
                    </div>

                    <div class="field">
                        <label for="algorithm" class="field-label">Algorithm</label>
                        <select id="algorithm" name="algorithm" class="select mt-2" data-review-algorithm>
                            <option>SHA1</option>
                            <option>SHA256</option>
                            <option>SHA512</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="field">
                            <label for="digits" class="field-label">Digits</label>
                            <select id="digits" name="digits" class="select mt-2" data-review-digits>
                                <option value="6">6</option>
                                <option value="8">8</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="period" class="field-label">Period</label>
                            <input id="period" name="period" type="number" min="15" max="300" value="30" required class="input tabular mt-2" data-review-period>
                        </div>
                    </div>

                    <div class="field">
                        <label for="vault_id_display" class="field-label">Vault</label>
                        <input id="vault_id_display" value="{{ $vault->name }}" disabled class="input mt-2">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary btn-block" data-submit-button data-loading-label="Encrypting and saving…">Save authenticator</button>
                    </div>

                    <label class="alert alert-warning hidden sm:col-span-2" data-duplicate-confirm>
                        <input type="checkbox" name="confirm_duplicate" value="1" class="checkbox mt-0.5">
                        <span>I understand this configuration already exists and want another copy.</span>
                    </label>

                    <div class="alert alert-error hidden sm:col-span-2" role="alert" data-form-errors></div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>

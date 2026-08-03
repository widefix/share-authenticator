import { BrowserQRCodeReader } from '@zxing/browser';
import { parseOtpAuthUri } from './authenticator-uri.js';

const root = document.querySelector('[data-authenticator-intake]');

if (root) {
    const tabs = [...root.querySelectorAll('[data-intake-tab]')];
    const panels = [...root.querySelectorAll('[data-intake-panel]')];
    const error = root.querySelector('[data-intake-error]');
    const review = root.querySelector('[data-authenticator-review]');
    const reader = new BrowserQRCodeReader();
    let cameraControls = null;

    const showError = (message) => {
        error.textContent = message;
        error.classList.remove('hidden');
    };

    const clearError = () => {
        error.textContent = '';
        error.classList.add('hidden');
    };

    const stopCamera = () => {
        cameraControls?.stop();
        cameraControls = null;
        const video = root.querySelector('[data-qr-video]');
        video.srcObject = null;
        video.classList.add('hidden');
        root.querySelector('[data-camera-placeholder]').classList.remove('hidden');
        root.querySelector('[data-camera-start]').classList.remove('hidden');
        root.querySelector('[data-camera-stop]').classList.add('hidden');
    };

    const selectTab = (name) => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.intakeTab === name;
            tab.setAttribute('aria-selected', String(selected));
            tab.classList.toggle('bg-white', selected);
            tab.classList.toggle('text-stone-950', selected);
            tab.classList.toggle('shadow-sm', selected);
            tab.classList.toggle('dark:bg-stone-800', selected);
            tab.classList.toggle('dark:text-white', selected);
        });
        panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.intakePanel !== name));
        if (name !== 'camera') stopCamera();
        clearError();
    };

    const populateReview = (data) => {
        root.querySelector('[data-review-issuer]').value = data.issuer;
        root.querySelector('[data-review-account]').value = data.account;
        root.querySelector('[data-review-secret]').value = data.secret;
        root.querySelector('[data-review-algorithm]').value = data.algorithm;
        root.querySelector('[data-review-digits]').value = String(data.digits);
        root.querySelector('[data-review-period]').value = String(data.period);
        review.classList.remove('hidden');
        review.scrollIntoView({ behavior: 'smooth', block: 'start' });
        review.querySelector('[data-review-issuer]').focus({ preventScroll: true });
        clearError();
    };

    const handlePayload = (payload) => {
        try {
            populateReview(parseOtpAuthUri(payload));
            stopCamera();
        } catch (caught) {
            showError(caught instanceof Error ? caught.message : 'We could not read that authenticator.');
        }
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => selectTab(tab.dataset.intakeTab)));
    selectTab('camera');

    root.querySelector('[data-camera-start]').addEventListener('click', async () => {
        clearError();
        const video = root.querySelector('[data-qr-video]');
        try {
            cameraControls = await reader.decodeFromConstraints(
                { video: { facingMode: { ideal: 'environment' } } },
                video,
                (result) => result && handlePayload(result.getText()),
            );
            video.classList.remove('hidden');
            root.querySelector('[data-camera-placeholder]').classList.add('hidden');
            root.querySelector('[data-camera-start]').classList.add('hidden');
            root.querySelector('[data-camera-stop]').classList.remove('hidden');
        } catch {
            showError('Camera access failed. Choose an image or enter the authenticator manually.');
            stopCamera();
        }
    });
    root.querySelector('[data-camera-stop]').addEventListener('click', stopCamera);

    const decodeImage = async (file) => {
        if (!file || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
            showError('Choose a PNG, JPEG, or WebP image.');
            return;
        }

        const url = URL.createObjectURL(file);
        try {
            const result = await reader.decodeFromImageUrl(url);
            handlePayload(result.getText());
        } catch {
            showError('No readable TOTP QR code was found in that image.');
        } finally {
            URL.revokeObjectURL(url);
        }
    };

    root.querySelector('[data-qr-image]').addEventListener('change', (event) => decodeImage(event.target.files?.[0]));
    const drop = root.querySelector('[data-image-drop]');
    drop.addEventListener('dragover', (event) => event.preventDefault());
    drop.addEventListener('drop', (event) => {
        event.preventDefault();
        decodeImage(event.dataTransfer?.files?.[0]);
    });
    document.addEventListener('paste', (event) => {
        const image = [...(event.clipboardData?.items ?? [])].find((item) => item.type.startsWith('image/'));
        if (image) {
            selectTab('image');
            decodeImage(image.getAsFile());
        }
    });

    root.querySelector('[data-parse-uri]').addEventListener('click', () => handlePayload(root.querySelector('[data-uri-input]').value));
    root.querySelector('[data-manual-review]').addEventListener('click', () => {
        populateReview({ issuer: '', account: '', secret: '', algorithm: 'SHA1', digits: 6, period: 30 });
    });

    document.addEventListener('visibilitychange', () => document.hidden && stopCamera());
    window.addEventListener('pagehide', stopCamera);
}

document.querySelectorAll('[data-authenticator-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;

        const button = form.querySelector('[data-submit-button]');
        const errors = form.querySelector('[data-form-errors]');
        button.disabled = true;
        const originalLabel = button.textContent;
        button.textContent = button.dataset.loadingLabel;
        errors?.classList.add('hidden');

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok) {
                const messages = Object.values(payload.errors ?? {}).flat();
                if (payload.errors?.duplicate) form.querySelector('[data-duplicate-confirm]')?.classList.remove('hidden');
                throw new Error(messages.join(' ') || 'We could not save this authenticator.');
            }
            window.location.assign(payload.redirect);
        } catch (caught) {
            if (errors) {
                errors.textContent = caught instanceof Error ? caught.message : 'We could not save this authenticator.';
                errors.classList.remove('hidden');
                errors.focus?.();
            }
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
});

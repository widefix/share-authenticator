import './authenticator-intake.js';
import './authenticator-list.js';
import { showToast } from './toast.js';

document.querySelectorAll('[data-submitting-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) {
            return;
        }

        const button = form.querySelector('[data-submit-button]');

        if (!button) {
            return;
        }

        button.disabled = true;
        button.textContent = button.dataset.loadingLabel;
    });
});

document.querySelectorAll('[data-copy-invitation-link]').forEach((button) => {
    button.addEventListener('click', async () => {
        const input = document.querySelector('[data-invitation-link]');
        if (!input) return;

        try {
            await navigator.clipboard.writeText(input.value);
            showToast('Invitation link copied.');
        } catch {
            input.select();
            showToast('Copy failed. Select and copy the link manually.', 'error');
        }
    });
});

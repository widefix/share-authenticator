import './authenticator-intake.js';
import './authenticator-list.js';

const formatDuration = (milliseconds) => {
    const totalSeconds = Math.max(0, Math.ceil(milliseconds / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = String(totalSeconds % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
};

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

document.querySelectorAll('[data-otp-input]').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 6);
    });
});

const expiry = document.querySelector('[data-expiry-target]');
const resend = document.querySelector('[data-resend-target]');

if (expiry || resend) {
    const updateTimers = () => {
        const now = Date.now();

        if (expiry) {
            const remaining = Number(expiry.dataset.expiryTarget) - now;
            expiry.textContent = remaining > 0
                ? `This code expires in ${formatDuration(remaining)}.`
                : 'This code has expired. Request a new one.';
        }

        if (resend) {
            const remaining = Number(resend.dataset.resendTarget) - now;
            resend.disabled = remaining > 0;
            resend.textContent = remaining > 0
                ? `Send another code in ${Math.ceil(remaining / 1000)}s`
                : 'Send another code';
        }
    };

    updateTimers();
    window.setInterval(updateTimers, 1000);
}

const region = () => document.querySelector('[data-toast-region]');

export const showToast = (message, tone = 'neutral') => {
    const host = region();

    if (!host) {
        return;
    }

    const toast = document.createElement('p');
    toast.className = tone === 'error' ? 'toast toast-error' : 'toast';
    toast.textContent = message;
    host.append(toast);

    window.setTimeout(() => {
        toast.classList.add('toast-leaving');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, 2600);
};

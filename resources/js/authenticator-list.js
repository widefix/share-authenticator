import { showToast } from './toast.js';

const root = document.querySelector('[data-authenticator-index]');

if (root) {
    const csrf = root.dataset.csrfToken;
    const search = root.querySelector('[data-authenticator-search]');
    const activeList = root.querySelector('[data-active-list]');
    const archivedList = root.querySelector('[data-archived-list]');
    const announcer = root.querySelector('[data-authenticator-announcer]');
    let authenticators = [];
    const timers = new Map();

    const request = async (url) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error(response.status === 429 ? 'Too many code requests. Wait a moment and try again.' : 'The code could not be retrieved.');
        return response.json();
    };

    const form = (action, method, label, style) => {
        const element = document.createElement('form');
        element.method = 'POST';
        element.action = action;
        element.innerHTML = `<input type="hidden" name="_token"><button type="submit"></button>`;
        element.querySelector('input').value = csrf;
        if (method !== 'POST') {
            const override = document.createElement('input');
            override.type = 'hidden'; override.name = '_method'; override.value = method; element.append(override);
        }
        const button = element.querySelector('button');
        button.type = 'submit'; button.textContent = label; button.className = style;
        return element;
    };

    const report = (message, tone = 'neutral') => {
        announcer.textContent = message;
        showToast(message, tone);
    };

    const showCode = async (item, card, purpose = 'reveal') => {
        const button = card.querySelector(`[data-${purpose}]`);
        button.disabled = true;
        try {
            const payload = await request(purpose === 'copy' ? item.copy_url : item.reveal_url);
            const code = payload.data.code;
            if (purpose === 'copy') {
                await navigator.clipboard.writeText(code);
                report(`${item.issuer} code copied.`);
                button.textContent = 'Copied';
                window.setTimeout(() => { button.textContent = 'Copy'; }, 1600);
            } else {
                const display = card.querySelector('[data-code]');
                const groupSize = payload.data.digits === 8 ? 4 : 3;
                display.textContent = code.replace(new RegExp(`(.{${groupSize}})(?=.)`, 'gu'), '$1 ');
                display.closest('[data-code-panel]').classList.remove('hidden');
                const serverOffset = new Date(payload.data.server_time).getTime() - Date.now();
                timers.set(item.id, { expires: new Date(payload.data.expires_at).getTime(), serverOffset, item, card });
                button.textContent = 'Refresh';
            }
        } catch (caught) {
            report(caught instanceof Error ? caught.message : 'The code could not be retrieved.', 'error');
        } finally {
            button.disabled = false;
        }
    };

    const activeCard = (item) => {
        const card = document.createElement('article');
        card.className = 'card p-5';
        card.innerHTML = `<div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="eyebrow truncate" data-issuer></p><h2 class="heading-md mt-1.5 truncate text-ink" data-account></h2><p class="caption mt-1" data-settings></p></div><a class="btn btn-ghost btn-sm" data-edit>Edit</a></div><div class="code-panel mt-5 hidden" data-code-panel><div class="flex items-center justify-between gap-4"><code class="code-value" data-code></code><span class="caption tabular text-white/70" data-countdown></span></div><div class="code-track mt-3"><div class="code-bar" data-progress></div></div></div><div class="mt-5 flex flex-wrap items-center gap-2"><button type="button" class="btn btn-primary btn-sm" data-reveal>Show code</button><button type="button" class="btn btn-neutral btn-sm" data-copy>Copy</button><span class="ml-auto" data-archive></span></div>`;
        card.querySelector('[data-issuer]').textContent = item.issuer;
        card.querySelector('[data-account]').textContent = item.account;
        card.querySelector('[data-settings]').textContent = `${item.algorithm} · ${item.digits} digits · ${item.period}s`;
        const edit = card.querySelector('[data-edit]');
        if (item.can_manage) {
            edit.href = item.edit_url;
        } else {
            edit.remove();
        }
        card.querySelector('[data-reveal]').addEventListener('click', () => showCode(item, card));
        card.querySelector('[data-copy]').addEventListener('click', () => showCode(item, card, 'copy'));
        if (item.can_manage) {
            card.querySelector('[data-archive]').append(form(item.archive_url, 'POST', 'Archive', 'btn btn-ghost btn-sm'));
        }
        return card;
    };

    const archivedCard = (item) => {
        const card = document.createElement('article');
        card.className = 'card p-5';
        const heading = document.createElement('h3'); heading.className = 'heading-sm text-ink'; heading.textContent = `${item.issuer}: ${item.account}`;
        const actions = document.createElement('div'); actions.className = 'mt-4 flex items-center gap-2';
        actions.append(form(item.restore_url, 'DELETE', 'Restore', 'btn btn-secondary btn-sm'));
        const details = document.createElement('details'); details.className = 'mt-3 border-t border-hairline pt-3';
        const summary = document.createElement('summary'); summary.className = 'text-sm font-medium text-ruby-deep'; summary.textContent = 'Delete';
        const deleteForm = form(item.delete_url, 'DELETE', 'Delete permanently', 'btn btn-danger btn-sm mt-3 w-full');
        deleteForm.className = 'alert-danger-zone mt-3';
        const hint = document.createElement('p'); hint.className = 'text-xs leading-5 text-ruby-deep'; hint.textContent = 'Type the full name to permanently delete this authenticator.';
        const confirm = document.createElement('input'); confirm.name = 'confirmation'; confirm.required = true; confirm.placeholder = `${item.issuer}: ${item.account}`; confirm.className = 'input mt-3';
        deleteForm.prepend(hint, confirm); details.append(summary, deleteForm); card.append(heading, actions, details);
        return card;
    };

    const render = () => {
        const query = search.value.trim().toLocaleLowerCase();
        const filtered = authenticators.filter((item) => `${item.issuer} ${item.account}`.toLocaleLowerCase().includes(query));
        const active = filtered.filter((item) => !item.archived);
        const archived = filtered.filter((item) => item.archived);
        activeList.replaceChildren(...active.map(activeCard));
        archivedList.replaceChildren(...archived.map(archivedCard));
        activeList.classList.toggle('hidden', active.length === 0);
        root.querySelector('[data-active-empty]').classList.toggle('hidden', active.length > 0);
        root.querySelector('[data-archived-section]').classList.toggle('hidden', archived.length === 0);
        root.querySelector('[data-active-count]').textContent = authenticators.filter((item) => !item.archived).length;
        root.querySelector('[data-archived-count]').textContent = authenticators.filter((item) => item.archived).length;
    };

    search.addEventListener('input', render);

    document.addEventListener('keydown', (event) => {
        if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
        const target = event.target;
        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement) return;
        event.preventDefault();
        search.focus();
    });

    fetch(root.dataset.feedUrl, { headers: { Accept: 'application/json' } })
        .then((response) => response.ok ? response.json() : Promise.reject())
        .then((payload) => {
            authenticators = payload.data;
            const drift = Math.abs(new Date(payload.meta.server_time).getTime() - Date.now());
            root.querySelector('[data-clock-warning]').classList.toggle('hidden', drift <= 5000);
            root.querySelector('[data-authenticator-loading]').classList.add('hidden');
            root.querySelector('[data-authenticator-content]').classList.remove('hidden');
            render();
        })
        .catch(() => {
            root.querySelector('[data-authenticator-loading]').classList.add('hidden');
            const error = root.querySelector('[data-authenticator-error]');
            error.textContent = 'Authenticators could not be loaded. Refresh the page to try again.';
            error.classList.remove('hidden');
        });

    window.setInterval(() => {
        timers.forEach((timer, id) => {
            const remaining = timer.expires - (Date.now() + timer.serverOffset);
            if (remaining <= 0) {
                timers.delete(id);
                showCode(timer.item, timer.card);
                return;
            }
            const seconds = Math.ceil(remaining / 1000);
            timer.card.querySelector('[data-countdown]').textContent = `${seconds}s`;
            const bar = timer.card.querySelector('[data-progress]');
            bar.style.width = `${Math.max(0, Math.min(100, remaining / (timer.item.period * 10)))}%`;
            bar.classList.toggle('code-bar-urgent', seconds <= 5);
        });
    }, 250);
}

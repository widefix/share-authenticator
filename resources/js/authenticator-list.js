const root = document.querySelector('[data-authenticator-index]');

if (root) {
    const csrf = root.dataset.csrfToken;
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

    const showCode = async (item, card, purpose = 'reveal') => {
        const button = card.querySelector(`[data-${purpose}]`);
        button.disabled = true;
        try {
            const payload = await request(purpose === 'copy' ? item.copy_url : item.reveal_url);
            const code = payload.data.code;
            if (purpose === 'copy') {
                await navigator.clipboard.writeText(code);
                announcer.textContent = `${item.issuer} code copied.`;
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
            announcer.textContent = caught instanceof Error ? caught.message : 'The code could not be retrieved.';
        } finally {
            button.disabled = false;
        }
    };

    const activeCard = (item) => {
        const card = document.createElement('article');
        card.className = 'rounded-2xl bg-white p-5 shadow-[0_12px_36px_rgba(41,37,36,0.06)] dark:bg-stone-900 dark:shadow-none';
        card.innerHTML = `<div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold tracking-[0.1em] text-emerald-700 uppercase dark:text-emerald-400" data-issuer></p><h2 class="mt-2 text-lg font-semibold text-stone-950 dark:text-white" data-account></h2><p class="mt-1 text-xs text-stone-400" data-settings></p></div><a class="rounded-lg px-3 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:hover:bg-stone-800" data-edit>Edit</a></div><div class="mt-6 hidden rounded-xl bg-stone-950 p-4 text-white" data-code-panel><div class="flex items-center justify-between gap-4"><code class="text-2xl font-semibold tracking-[0.16em] tabular-nums" data-code></code><span class="text-xs tabular-nums text-stone-400" data-countdown></span></div><div class="mt-3 h-1 overflow-hidden rounded bg-stone-800"><div class="h-full bg-emerald-500" data-progress></div></div></div><div class="mt-5 flex flex-wrap items-center gap-2"><button type="button" class="h-10 rounded-lg bg-stone-900 px-4 text-sm font-semibold text-white hover:bg-stone-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:opacity-60 dark:bg-stone-100 dark:text-stone-950" data-reveal>Reveal</button><button type="button" class="h-10 rounded-lg border border-stone-300 px-4 text-sm font-semibold hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 disabled:opacity-60 dark:border-stone-700 dark:hover:bg-stone-800" data-copy>Copy</button><span class="ml-auto" data-archive></span></div>`;
        card.querySelector('[data-issuer]').textContent = item.issuer;
        card.querySelector('[data-account]').textContent = item.account;
        card.querySelector('[data-settings]').textContent = `${item.algorithm} · ${item.digits} digits · ${item.period}s`;
        card.querySelector('[data-edit]').href = item.edit_url;
        card.querySelector('[data-reveal]').addEventListener('click', () => showCode(item, card));
        card.querySelector('[data-copy]').addEventListener('click', () => showCode(item, card, 'copy'));
        card.querySelector('[data-archive]').append(form(item.archive_url, 'POST', 'Archive', 'h-10 rounded-lg px-3 text-sm font-medium text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-800'));
        return card;
    };

    const archivedCard = (item) => {
        const card = document.createElement('article');
        card.className = 'rounded-xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900';
        const heading = document.createElement('h3'); heading.className = 'font-semibold'; heading.textContent = `${item.issuer}: ${item.account}`;
        const actions = document.createElement('div'); actions.className = 'mt-4 flex items-center gap-2';
        actions.append(form(item.restore_url, 'DELETE', 'Restore', 'h-10 rounded-lg px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950'));
        const details = document.createElement('details'); details.className = 'ml-auto';
        const summary = document.createElement('summary'); summary.className = 'cursor-pointer text-sm font-medium text-red-700 dark:text-red-400'; summary.textContent = 'Delete';
        const deleteForm = form(item.delete_url, 'DELETE', 'Delete permanently', 'mt-3 h-10 rounded-lg bg-red-700 px-3 text-sm font-semibold text-white');
        const confirm = document.createElement('input'); confirm.name = 'confirmation'; confirm.required = true; confirm.placeholder = `${item.issuer}: ${item.account}`; confirm.className = 'mt-3 h-10 w-full rounded-lg border border-red-300 bg-white px-3 text-sm text-stone-950 dark:border-red-800 dark:bg-stone-950 dark:text-white';
        deleteForm.prepend(confirm); details.append(summary, deleteForm); actions.append(details); card.append(heading, actions);
        return card;
    };

    const render = () => {
        const query = root.querySelector('[data-authenticator-search]').value.trim().toLocaleLowerCase();
        const filtered = authenticators.filter((item) => `${item.issuer} ${item.account}`.toLocaleLowerCase().includes(query));
        const active = filtered.filter((item) => !item.archived);
        const archived = filtered.filter((item) => item.archived);
        activeList.replaceChildren(...active.map(activeCard));
        archivedList.replaceChildren(...archived.map(archivedCard));
        root.querySelector('[data-active-empty]').classList.toggle('hidden', active.length > 0);
        root.querySelector('[data-archived-section]').classList.toggle('hidden', archived.length === 0);
        root.querySelector('[data-active-count]').textContent = authenticators.filter((item) => !item.archived).length;
        root.querySelector('[data-archived-count]').textContent = authenticators.filter((item) => item.archived).length;
    };

    root.querySelector('[data-authenticator-search]').addEventListener('input', render);

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
            timer.card.querySelector('[data-countdown]').textContent = `${Math.ceil(remaining / 1000)}s`;
            timer.card.querySelector('[data-progress]').style.width = `${Math.max(0, Math.min(100, remaining / (timer.item.period * 10)))}%`;
        });
    }, 250);
}

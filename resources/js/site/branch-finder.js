import { $, $$ } from './util';

/*
 * "Choose myself" or "Find the nearest café" above a form's branch select.
 * The locator (/find-a-cafe) is its own page, so whatever the visitor already
 * typed is kept in sessionStorage on the way out and put back on return; the
 * café comes back as ?branch=… (the server pre-selects it, so this works
 * without JavaScript too) with its driving time in sessionStorage.
 */

const NEAREST_KEY = 'koba:nearest';
const draftKey = (name) => `koba:draft:${name}`;
const FRESH_MS = 30 * 60 * 1000;

const read = (key) => {
    try { return JSON.parse(sessionStorage.getItem(key) || 'null'); } catch { return null; }
};
const write = (key, value) => {
    try { sessionStorage.setItem(key, JSON.stringify(value)); } catch { /* private mode: the form just starts empty */ }
};
const forget = (key) => {
    try { sessionStorage.removeItem(key); } catch { /* nothing stored */ }
};

const minutes = (s) => `${Math.max(1, Math.round(s / 60))} min`;
const kilometres = (m) => (m < 950 ? `${Math.round(m / 10) * 10} m` : `${(m / 1000).toFixed(1)} km`);

function saveDraft(form, name) {
    const fields = {};
    $$('input, select, textarea', form).forEach((el) => {
        if (!el.name || el.name === '_token' || el.name.startsWith('items[') || ['hidden', 'file', 'password', 'submit'].includes(el.type)) return;
        if (el.type === 'radio' || el.type === 'checkbox') {
            if (el.checked) fields[el.name] = el.value;
            return;
        }
        if (el.value) fields[el.name] = el.value;
    });
    write(draftKey(name), { at: Date.now(), fields });
}

function restoreDraft(form, name, { keepBranch }) {
    const draft = read(draftKey(name));
    forget(draftKey(name));
    if (!draft || Date.now() - draft.at > FRESH_MS) return;

    Object.entries(draft.fields).forEach(([field, value]) => {
        if (field === 'branch' && keepBranch) return; // the locator's choice wins
        $$(`[name="${CSS.escape(field)}"]`, form).forEach((el) => {
            if (el.type === 'radio' || el.type === 'checkbox') el.checked = el.value === value;
            else el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

/** @returns {boolean} true when the visitor just came back from the locator with a café. */
export function initBranchFinder(form) {
    const finder = $('[data-branch-finder]', form);
    if (!finder) return false;

    const name = finder.dataset.branchFinder;
    const select = document.getElementById(finder.dataset.select);
    const self = $('[data-branch-self]', finder);
    const near = $('[data-branch-near]', finder);
    const found = $('[data-branch-found]', finder);

    const mode = (which) => {
        self.classList.toggle('is-on', which === 'self');
        self.setAttribute('aria-pressed', String(which === 'self'));
        near.classList.toggle('is-on', which === 'near');
        if (which === 'self') found.hidden = true;
    };

    near.addEventListener('click', () => saveDraft(form, name)); // then the link opens the locator
    self.addEventListener('click', () => {
        mode('self');
        select.focus();
        try { select.showPicker?.(); } catch { /* not supported here */ }
    });
    select.addEventListener('change', (event) => {
        if (event.isTrusted) mode('self'); // picked by hand after locating
    });

    const params = new URLSearchParams(window.location.search);
    const chosen = params.get('branch');
    const returned = Boolean(chosen) && [...select.options].some((option) => option.value === chosen && !option.disabled);

    restoreDraft(form, name, { keepBranch: returned });
    if (!returned) return false;

    select.value = chosen;
    const nearest = read(NEAREST_KEY);
    forget(NEAREST_KEY);
    const fromLocator = nearest && nearest.branch === chosen && Date.now() - nearest.at < FRESH_MS;
    // "KOBA Atlas" reads better in a sentence than the full "KOBA Patisserie & Bakery | Atlas".
    const branch = fromLocator && nearest.short ? `KOBA ${nearest.short}` : (select.selectedOptions[0]?.textContent.trim() ?? chosen);

    let message = finder.dataset.msgPicked;
    if (fromLocator && nearest.located && nearest.drive_s && !nearest.estimated) {
        message = finder.dataset.msgFound.replace(':time', minutes(nearest.drive_s)).replace(':distance', kilometres(nearest.road_m));
    } else if (fromLocator && nearest.located && nearest.road_m) {
        message = finder.dataset.msgFoundEstimated.replace(':distance', kilometres(nearest.road_m));
    }
    found.textContent = message.replace(':branch', branch);
    found.hidden = false;
    mode('near');

    // A reload shouldn't apply it again.
    params.delete('branch');
    const query = params.toString();
    window.history.replaceState(null, '', `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`);

    return true;
}

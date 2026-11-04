/*
 * KOBA dashboards (admin · branch · customer).
 * Alpine comes from Livewire's ESM build (Livewire ships it), so it is not
 * loaded twice; ApexCharts and Lucide are bundled by Vite.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import chart from './charts/chart';
import page, { kpiCard } from './dashboard/page';
import table from './dashboard/table';
import liveBoard from './dashboard/live-board';
import tracker from './dashboard/tracker';
import orderDrawer from './dashboard/order-drawer';
import commandPalette from './dashboard/command-palette';
import pickupVerification from './dashboard/pickup-verification';
import locator from './dashboard/locator';
import { pickupCode, pickupFullscreen } from './dashboard/pickup-pass';
import { addToCart } from './dashboard/cart-bridge';
import { badge, avatar, thumb } from './dashboard/cells';
import { icon, renderIcons, watchIcons } from './dashboard/icons';
import { format, clock } from './dashboard/format';
import { json } from './dashboard/http';
import { isDark, toggleTheme } from './dashboard/theme';

Alpine.magic('icon', () => icon);
Alpine.magic('fmt', () => (value, kind, abbreviate = false) => format(value, kind, { abbreviate }));
Alpine.magic('badge', () => badge);
Alpine.magic('avatar', () => avatar);
Alpine.magic('thumb', () => thumb);
Alpine.magic('clock', () => clock);

/* Inline SVG sparkline for table rows (no chart instance per row). */
Alpine.magic('spark', () => (values = [], { width = 128, height = 32 } = {}) => {
    if (!values.length) return '';
    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const step = width / Math.max(1, values.length - 1);
    const y = (v) => (height - 3 - ((v - min) / (max - min || 1)) * (height - 6)).toFixed(1);
    const line = values.map((v, i) => `${(i * step).toFixed(1)},${y(v)}`).join(' ');
    const rising = values[values.length - 1] >= values[0];
    return `<svg viewBox="0 0 ${width} ${height}" class="h-full w-full overflow-visible" preserveAspectRatio="none" aria-hidden="true">`
        + `<polyline points="0,${height} ${line} ${width},${height}" class="${rising ? 'fill-forest-500/10' : 'fill-danger/10'}" stroke="none"/>`
        + `<polyline points="${line}" fill="none" class="${rising ? 'stroke-forest-500' : 'stroke-danger'}" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>`;
});

Alpine.store('theme', {
    dark: isDark(),
    toggle() { toggleTheme(); },
});
window.addEventListener('theme-changed', (event) => { Alpine.store('theme').dark = event.detail.dark; });

Alpine.data('chart', chart);
Alpine.data('page', page);
Alpine.data('kpi', kpiCard);
Alpine.data('dataTable', table);
Alpine.data('liveBoard', liveBoard);
Alpine.data('tracker', tracker);
Alpine.data('orderDrawer', orderDrawer);
Alpine.data('commandPalette', commandPalette);
Alpine.data('pickupVerification', pickupVerification);
Alpine.data('locator', locator);
Alpine.data('pickupCode', pickupCode);
Alpine.data('pickupFullscreen', pickupFullscreen);

/* App shell: collapsible sidebar (remembered) and the mobile drawer. */
Alpine.data('shell', () => ({
    collapsed: false,
    drawer: false,
    init() {
        try { this.collapsed = localStorage.getItem('koba:sidebar') === 'collapsed'; } catch (e) { /* default open */ }
        this.$watch('drawer', (open) => document.body.classList.toggle('overflow-hidden', open));
    },
    toggle() {
        this.collapsed = !this.collapsed;
        try { localStorage.setItem('koba:sidebar', this.collapsed ? 'collapsed' : 'open'); } catch (e) { /* not remembered */ }
        // Charts measure their container: let them re-fit after the sidebar animates.
        setTimeout(() => window.dispatchEvent(new Event('resize')), 220);
    },
}));

/* Toast stack: $dispatch('toast', { type, message, detail?, link? }). */
Alpine.data('toasts', () => ({
    items: [],
    push({ type = 'info', message, detail = null, link = null }) {
        const id = Date.now() + Math.random();
        this.items.push({ id, type, message, detail, link });
        setTimeout(() => this.dismiss(id), type === 'error' ? 7000 : 4500);
    },
    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
}));

/* Confirmation modal: $dispatch('confirm', { title, body, confirm, danger, then: () => … }). */
Alpine.data('confirmModal', () => ({
    open: false,
    title: '',
    body: '',
    confirmLabel: 'Confirm',
    danger: false,
    then: null,
    ask({ title, body = '', confirm = 'Confirm', danger = false, then }) {
        Object.assign(this, { title, body, confirmLabel: confirm, danger, then, open: true });
        this.$nextTick(() => this.$refs.confirm?.focus());
    },
    accept() {
        this.open = false;
        this.then?.();
    },
}));

/* Small JSON form helper for settings / toggles: posts and toasts the result. */
Alpine.data('remoteForm', ({ url, method = 'PUT', values = {} }) => ({
    values,
    saving: false,
    errors: {},
    async submit(extra = {}) {
        this.saving = true;
        this.errors = {};
        try {
            const data = await json(url, { method, body: { ...this.values, ...extra } });
            this.$dispatch('toast', { type: 'success', message: data.message || 'Saved.' });
            this.$dispatch('saved', data);
            return data;
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
            return null;
        } finally {
            this.saving = false;
        }
    },
}));

/* POST/PATCH a JSON endpoint from anywhere: $action(url, body, method). */
Alpine.magic('action', (el) => async (url, body = {}, method = 'PATCH') => {
    try {
        const data = await json(url, { method, body });
        if (data.message) el.dispatchEvent(new CustomEvent('toast', { bubbles: true, detail: { type: 'success', message: data.message } }));
        return data;
    } catch (error) {
        el.dispatchEvent(new CustomEvent('toast', { bubbles: true, detail: { type: 'error', message: error.message } }));
        return null;
    }
});

Alpine.magic('reorder', (el) => (items) => {
    const count = addToCart(items);
    el.dispatchEvent(new CustomEvent('toast', { bubbles: true, detail: { type: 'success', message: `${count} ${count === 1 ? 'item' : 'items'} added to your mesob.`, link: { href: '/menu', label: 'Open the menu' } } }));
});

/* Live wall clock in the branch header. */
Alpine.data('wallClock', () => ({
    now: '',
    init() {
        const tick = () => { this.now = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Africa/Addis_Ababa' }); };
        tick();
        this._t = setInterval(tick, 1000);
    },
    destroy() { clearInterval(this._t); },
}));

// Swap server-rendered icon placeholders before Alpine initialises them, then keep watching.
renderIcons();
Livewire.start();
watchIcons();

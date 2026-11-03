import { json, withQuery } from './http';
import { toggleTheme } from './theme';

/*
 * Ctrl/⌘ + K: jump to any page of this dashboard, or search orders and
 * customers (scoped on the server to what this role may see).
 */
export default ({ items, searchUrl }) => ({
    open: false,
    q: '',
    active: 0,
    remote: [],
    searching: false,

    init() {
        window.addEventListener('keydown', (event) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.open ? this.close() : this.show();
            }
        });
        this.$watch('q', () => { this.active = 0; this.search(); });
    },

    show() {
        this.open = true;
        this.q = '';
        this.remote = [];
        this.$nextTick(() => this.$refs.input?.focus());
    },

    close() {
        this.open = false;
    },

    get results() {
        const q = this.q.trim().toLowerCase();
        const local = items
            .filter((item) => !q || item.label.toLowerCase().includes(q) || (item.keywords || '').includes(q))
            .slice(0, q ? 6 : 12);
        return [...local, ...this.remote];
    },

    search() {
        clearTimeout(this._debounce);
        if (this.q.trim().length < 2) { this.remote = []; return; }
        this._debounce = setTimeout(async () => {
            this.searching = true;
            try {
                this.remote = (await json(withQuery(searchUrl, { q: this.q }))).results;
            } catch (e) {
                this.remote = [];
            } finally {
                this.searching = false;
            }
        }, 180);
    },

    move(step) {
        const count = this.results.length;
        if (count) this.active = (this.active + step + count) % count;
        this.$nextTick(() => this.$refs.list?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' }));
    },

    choose(item = this.results[this.active]) {
        if (!item) return;
        this.close();
        if (item.action === 'theme') return toggleTheme();
        if (item.order && document.querySelector('[data-order-drawer]')) return window.dispatchEvent(new CustomEvent('open-order', { detail: item.order }));
        if (item.href) window.location.href = item.href;
    },
});

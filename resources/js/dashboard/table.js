import { json, withQuery } from './http';
import { cell } from './cells';
import { escape } from './format';

/*
 * <x-dashboard.data-table>: sortable, searchable, paginated table with
 * filters and optional bulk selection. Two modes:
 *  - local: all rows embedded in the page (small lists)
 *  - remote: rows fetched page by page from `endpoint` (orders, payments)
 * On small screens the same rows render as cards.
 */
export default ({ columns, rows = [], endpoint = null, perPage = 12, sort = null, dir = 'desc', filters = {}, searchable = [], selectable = false, rowEvent = null }) => ({
    columns,
    all: rows,
    rows: [],
    total: 0,
    sum: 0,
    page: 1,
    pages: 1,
    perPage,
    q: '',
    sort,
    dir,
    filters: { ...filters },
    selected: [],
    loading: false,

    init() {
        const url = new URL(window.location.href);
        Object.keys(this.filters).forEach((key) => { if (url.searchParams.has(key)) this.filters[key] = url.searchParams.get(key); });
        if (url.searchParams.has('q')) this.q = url.searchParams.get('q');

        this.load();
        this.$watch('q', () => { this.page = 1; this.debounced(); });
        this.$watch('filters', () => { this.page = 1; this.load(); }, { deep: true });
        window.addEventListener('table:reload', () => this.load(true));
        // Local mode: a save that returns fresh rows swaps them in.
        window.addEventListener('table:rows', (event) => { this.all = event.detail; this.load(); });
        if (endpoint && this.$el.dataset.poll) {
            this._poll = setInterval(() => { if (!document.hidden) this.load(true); }, Number(this.$el.dataset.poll) * 1000);
        }
    },

    destroy() {
        clearInterval(this._poll);
    },

    debounced() {
        clearTimeout(this._debounce);
        this._debounce = setTimeout(() => this.load(), endpoint ? 250 : 0);
    },

    async load(silent = false) {
        if (endpoint) {
            this.loading = !silent;
            try {
                const data = await json(withQuery(endpoint, { ...this.filters, q: this.q, sort: this.sort, dir: this.dir, page: this.page, per_page: this.perPage }));
                Object.assign(this, { rows: data.rows, total: data.total, pages: data.pages, page: data.page, sum: data.sum });
            } catch (error) {
                this.$dispatch('toast', { type: 'error', message: error.message });
            } finally {
                this.loading = false;
            }
            return;
        }

        const q = this.q.trim().toLowerCase();
        let list = this.all.filter((row) => Object.entries(this.filters).every(([key, value]) => value === '' || value === null || String(row[key]) === String(value)));
        if (q) list = list.filter((row) => searchable.some((key) => String(row[key] ?? '').toLowerCase().includes(q)));
        if (this.sort) {
            const factor = this.dir === 'asc' ? 1 : -1;
            list = [...list].sort((a, b) => (a[this.sort] > b[this.sort] ? 1 : a[this.sort] < b[this.sort] ? -1 : 0) * factor);
        }
        this.total = list.length;
        this.sum = list.reduce((s, r) => s + (Number(r.total) || 0), 0);
        this.pages = Math.max(1, Math.ceil(list.length / this.perPage));
        this.page = Math.min(this.page, this.pages);
        this.rows = list.slice((this.page - 1) * this.perPage, this.page * this.perPage);
    },

    sortBy(column) {
        if (!column.sortable) return;
        this.dir = this.sort === column.key && this.dir === 'desc' ? 'asc' : 'desc';
        this.sort = column.key;
        this.load();
    },

    go(page) {
        this.page = Math.max(1, Math.min(this.pages, page));
        this.load();
    },

    render(column, row) {
        return cell(column, row);
    },

    rowId(row) {
        return row.id ?? row.key ?? row.email;
    },

    open(row) {
        if (rowEvent) this.$dispatch(rowEvent, row);
    },

    action(name, row) {
        this.$dispatch('row-action', { action: name, row });
    },

    // Bulk selection
    get allSelected() {
        return this.rows.length > 0 && this.rows.every((row) => this.selected.includes(this.rowId(row)));
    },
    toggleAll() {
        const ids = this.rows.map((row) => this.rowId(row));
        this.selected = this.allSelected ? this.selected.filter((id) => !ids.includes(id)) : [...new Set([...this.selected, ...ids])];
    },
    selectedRows() {
        return [...this.all, ...this.rows].filter((row, i, list) => this.selected.includes(this.rowId(row)) && list.findIndex((r) => this.rowId(r) === this.rowId(row)) === i);
    },

    /** Download the selected rows (or the current page) as CSV, right in the browser. */
    exportCsv(name = 'export') {
        const rows = this.selected.length ? this.selectedRows() : this.rows;
        const cols = this.columns.filter((c) => c.type !== 'actions');
        const quote = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
        const csv = [cols.map((c) => quote(c.label)).join(','), ...rows.map((row) => cols.map((c) => quote(row[c.key])).join(','))].join('\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([`# KOBA demo data\n${csv}`], { type: 'text/csv' }));
        link.download = `koba-${name}-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    },

    range() {
        if (!this.total) return 'No results';
        const from = (this.page - 1) * this.perPage + 1;
        return `${from}–${Math.min(this.total, this.page * this.perPage)} of ${this.total}`;
    },

    esc: escape,
});

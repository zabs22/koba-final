import { json, withQuery } from './http';
import { format } from './format';

/*
 * A dashboard page driven by one JSON payload (KPIs, charts, widgets).
 * The date range / branch filters refetch it from `endpoint` without a page
 * reload; while the range includes today it refreshes itself every few
 * seconds so figures, charts and lists stay live.
 */
export default ({ endpoint, payload, poll = 0, params = {} }) => ({
    payload,
    loading: false,
    failed: false,
    fresh: false,
    filters: { range: payload.range?.key ?? '7d', from: payload.range?.from ?? '', to: payload.range?.to ?? '', ...params },
    secondsAgo: 0,

    init() {
        this._updatedAt = Date.now();
        this._tick = setInterval(() => {
            this.secondsAgo = Math.round((Date.now() - this._updatedAt) / 1000);
            if (poll && this.payload.range?.live !== false && this.secondsAgo >= poll && !document.hidden && !this.loading) this.refresh(true);
        }, 1000);

        this.$watch('filters', () => this.refresh(), { deep: true });
        window.addEventListener('dashboard:refresh', () => this.refresh(true));
    },

    destroy() {
        clearInterval(this._tick);
    },

    setRange({ range, from, to }) {
        this.filters = { ...this.filters, range, from: from ?? '', to: to ?? '' };
    },

    set(key, value) {
        this.filters = { ...this.filters, [key]: this.filters[key] === value ? '' : value };
    },

    async refresh(silent = false) {
        this.loading = !silent;
        try {
            const query = { ...this.filters };
            if (query.range !== 'custom') { delete query.from; delete query.to; }
            this.payload = await json(withQuery(endpoint, query));
            this.failed = false;
            this._updatedAt = Date.now();
            this.secondsAgo = 0;
            this.fresh = true;
            setTimeout(() => { this.fresh = false; }, 900);
            if (!silent) window.history.replaceState(null, '', withQuery(window.location.href, query));
        } catch (error) {
            this.failed = true;
            if (!silent) this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.loading = false;
        }
    },

    kpi(key) {
        return this.payload.kpis?.[key] ?? {};
    },

    fmt(value, kind = 'number', abbreviate = false) {
        return format(value, kind, { abbreviate });
    },

    updatedLabel() {
        if (this.loading) return 'Updating…';
        if (this.secondsAgo < 5) return 'Updated just now';
        return this.secondsAgo < 60 ? `Updated ${this.secondsAgo}s ago` : `Updated ${Math.floor(this.secondsAgo / 60)} min ago`;
    },
});

/*
 * One KPI card inside a page: tweens its number when the payload changes and
 * exposes the change badge. Lower-is-better metrics (prep time, cancellations)
 * colour a fall as good.
 */
export const kpiCard = (key) => ({
    shown: 0,

    init() {
        this.shown = this.metric().value ?? 0;
        this.$watch(() => this.metric().value, (to) => this.tween(this.shown, to ?? 0));
    },

    metric() {
        return this.payload.kpis?.[key] ?? {};
    },

    tween(from, to) {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) { this.shown = to; return; }
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / 600);
            this.shown = from + (to - from) * (1 - (1 - t) ** 3);
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },

    display() {
        const m = this.metric();
        const value = m.format === 'number' ? Math.round(this.shown) : this.shown;
        return format(value, m.format);
    },

    trend() {
        const change = this.metric().change;
        if (change === null || change === undefined) return null;
        const good = this.metric().lowerIsBetter ? change <= 0 : change >= 0;
        return { up: change >= 0, good, label: `${change > 0 ? '+' : ''}${change.toFixed(1)}%` };
    },
});

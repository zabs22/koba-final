import { json } from './http';
import { duration, stopwatch } from './format';

const NEXT = { pending: 'preparing', preparing: 'ready', ready: 'completed' };
const SOUND_KEY = 'koba:live-sound';

/** A short two-note chime, generated (no audio file). */
function chime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [[880, 0], [1320, 0.14]].forEach(([freq, at]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime + at);
            gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + at + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + at + 0.45);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + at);
            osc.stop(ctx.currentTime + at + 0.5);
        });
        setTimeout(() => ctx.close(), 1200);
    } catch (e) { /* audio unavailable */ }
}

/*
 * Live Orders board (branch admin): New → Preparing → Ready → Picked up.
 * Polls the feed, flags new orders (chime + highlight), ticks timers every
 * second, and moves orders with a server-side status change.
 */
export default ({ feedUrl, moveUrl, simulateUrl, initial, poll = 15 }) => ({
    columns: initial.columns,
    later: initial.later,
    counts: initial.counts,
    status: initial.status,
    offset: initial.now - Date.now() / 1000,
    clock: initial.now,
    fresh: [],
    busy: [],
    sound: true,
    lastSync: Date.now(),
    syncing: false,

    init() {
        try { this.sound = localStorage.getItem(SOUND_KEY) !== 'off'; } catch (e) { /* default on */ }
        this.known = new Set(this.ids());
        this._tick = setInterval(() => {
            this.clock = Date.now() / 1000 + this.offset;
            if (Date.now() - this.lastSync >= poll * 1000 && !document.hidden && !this.syncing) this.refresh();
        }, 1000);
    },

    destroy() {
        clearInterval(this._tick);
    },

    ids() {
        return Object.values(this.columns).flat().map((o) => o.id);
    },

    async refresh() {
        this.syncing = true;
        try {
            const data = await json(feedUrl);
            const incoming = data.columns.pending.filter((o) => !this.known.has(o.id));
            Object.assign(this, { columns: data.columns, later: data.later, counts: data.counts, status: data.status, offset: data.now - Date.now() / 1000 });
            this.known = new Set([...this.known, ...this.ids()]);
            if (incoming.length) this.announce(incoming);
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.syncing = false;
            this.lastSync = Date.now();
        }
    },

    announce(orders) {
        this.fresh = [...this.fresh, ...orders.map((o) => o.id)];
        setTimeout(() => { this.fresh = this.fresh.filter((id) => !orders.some((o) => o.id === id)); }, 8000);
        if (this.sound) chime();
        orders.forEach((o) => this.$dispatch('toast', { type: 'order', message: `New order ${o.id} · ${o.customer}`, detail: `${o.items} · pickup ${o.pickup_time}` }));
    },

    toggleSound() {
        this.sound = !this.sound;
        try { localStorage.setItem(SOUND_KEY, this.sound ? 'on' : 'off'); } catch (e) { /* not remembered */ }
        if (this.sound) chime();
    },

    async move(order, to = NEXT[order.status]) {
        if (!to || this.busy.includes(order.id)) return;
        this.busy.push(order.id);
        const from = order.status;
        // Optimistic: move the card now, put it back if the server refuses.
        this.place(order, to);
        try {
            const data = await json(moveUrl.replace('__ID__', order.id), { method: 'PATCH', body: { status: to } });
            this.place(data.order, data.order.status);
            this.$dispatch('toast', { type: 'success', message: `${order.id} → ${data.order.status_label}` });
        } catch (error) {
            this.place(order, from);
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.busy = this.busy.filter((id) => id !== order.id);
        }
    },

    place(order, status) {
        Object.keys(this.columns).forEach((key) => { this.columns[key] = this.columns[key].filter((o) => o.id !== order.id); });
        if (this.columns[status]) {
            const moved = { ...order, status, status_at: Math.round(this.clock) };
            this.columns[status] = status === 'completed' ? [moved, ...this.columns[status]].slice(0, 6) : [...this.columns[status], moved].sort((a, b) => a.pickup_at - b.pickup_at);
        }
        this.counts = Object.fromEntries(Object.entries(this.columns).map(([k, v]) => [k, v.length]));
    },

    async simulate() {
        try {
            await json(simulateUrl, { method: 'POST' });
            await this.refresh();
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        }
    },

    /** Seconds since the last sync; reads `clock` so it re-renders every tick. */
    syncedAgo() {
        return this.clock && Math.max(0, Math.round((Date.now() - this.lastSync) / 1000));
    },

    elapsed(order) {
        return stopwatch(this.clock - order.placed_at);
    },

    /** "in 12 min" / "8 min late" relative to pickup. */
    due(order) {
        const left = order.pickup_at - this.clock;
        if (order.status === 'completed') return `picked up ${duration(this.clock - order.status_at)} ago`;
        return left >= 0 ? `pickup in ${duration(left)}` : `${duration(left)} late`;
    },

    /** ok | soon | late — amber within 5 minutes of pickup, red once late. */
    urgency(order) {
        if (order.status === 'completed') return 'ok';
        const readyBy = order.status === 'ready' ? order.pickup_at + 600 : order.pickup_at - 180;
        const left = readyBy - this.clock;
        if (left < 0) return 'late';
        return left < 300 ? 'soon' : 'ok';
    },

    action(status) {
        return { pending: 'Start preparing', preparing: 'Mark ready', ready: 'Picked up' }[status];
    },
});

import { json } from './http';
import { duration } from './format';

const STEPS = ['pending', 'preparing', 'ready', 'completed'];

/*
 * Customer's live order tracker: Confirmed → Preparing → Ready → Picked up.
 * Polls the active-order endpoint (or the confirmation page's status link)
 * and counts down to the ready/pickup time.
 */
export default ({ endpoint, demoUrl = null, initial, now, poll = 15 }) => ({
    order: initial,
    offset: now - Date.now() / 1000,
    clock: now,
    placing: false,
    rating: 0,

    /** The pickup pass (code + QR) for the pickup components. */
    get pass() { return this.order; },

    init() {
        this._tick = setInterval(() => { this.clock = Date.now() / 1000 + this.offset; }, 1000);
        this._poll = setInterval(() => { if (!document.hidden) this.refresh(); }, poll * 1000);
    },

    destroy() {
        clearInterval(this._tick);
        clearInterval(this._poll);
    },

    async refresh() {
        try {
            const data = await json(endpoint);
            const before = this.order?.status;
            // The status link sends no QR: keep the one we have for the same order.
            if (data.order && !data.order.qr && data.order.id === this.order?.id) data.order.qr = this.order.qr;
            this.order = data.order;
            this.offset = data.now - Date.now() / 1000;
            if (this.order && before && before !== this.order.status) {
                const messages = {
                    preparing: 'The kitchen has started your order.',
                    ready: `Your order is ready at ${this.order.branch_name}! Show code ${this.order.pickup_code} at the counter.`,
                    completed: 'Enjoy your order! ☕',
                    cancelled: `Order ${this.order.id} was cancelled.`,
                };
                if (messages[this.order.status]) this.$dispatch('toast', { type: this.order.status === 'ready' || this.order.status === 'completed' ? 'success' : 'info', message: messages[this.order.status] });
            }
        } catch (e) { /* try again next tick */ }
    },

    async placeDemo() {
        this.placing = true;
        try {
            const data = await json(demoUrl, { method: 'POST' });
            this.order = data.order;
            this.$dispatch('toast', { type: 'success', message: `Order ${data.order.id} sent to ${data.order.branch_name}.`, detail: 'Watch it move on the branch’s Live Orders board.' });
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.placing = false;
        }
    },

    /** Visual only for now: stars light up, nothing is saved. */
    rate(stars) {
        this.rating = stars;
        this.$dispatch('toast', { type: 'success', message: 'Thanks for rating your order!', detail: 'Demo: ratings are not saved yet.' });
    },

    stepIndex() {
        return this.order ? STEPS.indexOf(this.order.status) : -1;
    },

    /** 0–100 along the bar; creeps forward while the kitchen works. */
    progress() {
        if (!this.order) return 0;
        const i = this.stepIndex();
        if (i < 0) return 0;
        let extra = 0;
        if (this.order.status === 'preparing' && this.order.eta) {
            const total = this.order.eta.ready_at - this.order.status_at;
            extra = total > 0 ? Math.min(0.9, Math.max(0, (this.clock - this.order.status_at) / total)) : 0;
        }
        return Math.min(100, ((i + extra) / (STEPS.length - 1)) * 100);
    },

    headline() {
        if (!this.order) return '';
        return {
            pending: 'Order received',
            preparing: 'Being prepared',
            ready: 'Ready for pickup',
            completed: 'Picked up',
            cancelled: 'Cancelled',
        }[this.order.status];
    },

    countdown() {
        if (!this.order) return '';
        if (this.order.eta) {
            const left = this.order.eta.ready_at - this.clock;
            return left > 0 ? `Ready in about ${duration(left)}` : 'Almost ready';
        }
        if (this.order.status === 'ready') {
            const left = this.order.pickup_at - this.clock;
            return left > 0 ? `Pickup at ${this.order.pickup_time} · ${duration(left)} to go` : `Waiting for you since ${this.order.pickup_time}`;
        }
        return `Picked up at ${this.order.timeline?.[3]?.time ?? this.order.pickup_time}`;
    },
});

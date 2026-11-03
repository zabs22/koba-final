import { json } from './http';
import { addToCart } from './cart-bridge';

/*
 * Order detail slide-over, opened by `open-order` (from tables, charts,
 * the command palette) or ?order=ORD-… in the URL. Branch admins can move
 * the order on or cancel it from here.
 */
export default ({ showUrl, moveUrl = null, receiptUrl = null }) => ({
    open: false,
    loading: false,
    saving: false,
    order: null,

    init() {
        const id = new URL(window.location.href).searchParams.get('order');
        if (id) this.show(id);
    },

    async show(id) {
        if (typeof id === 'object') id = id.id;
        this.open = true;
        this.loading = true;
        try {
            this.order = await json(showUrl.replace('__ID__', id));
        } catch (error) {
            this.open = false;
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.loading = false;
        }
    },

    close() {
        this.open = false;
    },

    async move(status) {
        if (!moveUrl || this.saving) return;
        this.saving = true;
        try {
            const data = await json(moveUrl.replace('__ID__', this.order.id), { method: 'PATCH', body: { status } });
            this.order = data.order;
            window.dispatchEvent(new CustomEvent('table:reload'));
            window.dispatchEvent(new CustomEvent('dashboard:refresh'));
            this.$dispatch('toast', { type: 'success', message: `${this.order.id} is now ${this.order.status_label.toLowerCase()}.` });
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.saving = false;
        }
    },

    receipt() {
        if (receiptUrl) window.open(receiptUrl.replace('__ID__', this.order.id), '_blank', 'noopener');
    },

    reorder() {
        const count = addToCart(this.order.lines.map((line) => line.cart));
        this.$dispatch('toast', { type: 'success', message: `${count} ${count === 1 ? 'item' : 'items'} added to your mesob.`, link: { href: '/menu', label: 'Open the menu' } });
    },

    nextLabel() {
        return { pending: 'Start preparing', preparing: 'Mark ready', ready: 'Mark picked up' }[this.order?.status];
    },
});

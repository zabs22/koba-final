import { $, $$ } from './util';

/*
 * Cart: kept in this browser only (localStorage), no prices. Checkout hands the
 * items to the existing order form, which our team confirms by phone.
 */
const KEY = 'koba:cart';

const load = () => {
    try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
};
const save = (items) => {
    try { localStorage.setItem(KEY, JSON.stringify(items)); } catch (e) { /* storage blocked: cart lasts this page view */ }
};

export function initCart() {
    const root = $('[data-cart]');
    if (!root) return;

    const list = $('[data-cart-list]', root);
    const empty = $('[data-cart-empty]', root);
    const foot = $('[data-cart-foot]', root);
    const checkout = $('[data-cart-checkout]', root);
    const panel = $('.cart__panel', root);
    const openers = $$('[data-cart-open]');
    const counts = $$('[data-cart-count]');
    let items = load();
    let lastFocus = null;

    const el = (tag, cls, text) => {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text != null) node.textContent = text;
        return node;
    };

    const render = () => {
        const total = items.reduce((sum, item) => sum + item.qty, 0);
        counts.forEach((c) => { c.textContent = total; c.hidden = total === 0; });

        list.replaceChildren(...items.map((item) => {
            const row = el('li', 'cart__item');
            if (item.image) {
                const img = el('img', 'cart__img');
                img.src = item.image;
                img.alt = '';
                img.loading = 'lazy';
                row.append(img);
            }
            const text = el('div', 'cart__text');
            text.append(el('p', 'cart__name', item.title), el('p', 'cart__plain', item.name));

            const qty = el('div', 'cart__qty');
            const less = el('button', 'cart__step', '−');
            less.type = 'button';
            less.setAttribute('aria-label', `${list.dataset.labelLess}: ${item.title}`);
            less.addEventListener('click', () => change(item.slug, -1));
            const n = el('span', 'cart__n', item.qty);
            const more = el('button', 'cart__step', '+');
            more.type = 'button';
            more.setAttribute('aria-label', `${list.dataset.labelMore}: ${item.title}`);
            more.addEventListener('click', () => change(item.slug, 1));
            qty.append(less, n, more);

            const remove = el('button', 'cart__remove', list.dataset.labelRemove);
            remove.type = 'button';
            remove.addEventListener('click', () => change(item.slug, -item.qty));

            text.append(qty);
            row.append(text, remove);
            return row;
        }));

        empty.hidden = items.length > 0;
        foot.hidden = items.length === 0;

        // Pre-select the first cake on the order form; the full list goes into the notes.
        const cake = items.find((item) => item.cake);
        const url = new URL(checkout.href, location.href);
        url.search = '';
        url.searchParams.set('from', 'cart');
        if (cake) url.searchParams.set('cake', cake.slug);
        checkout.href = url.toString();
    };

    const change = (slug, delta) => {
        items = items
            .map((item) => (item.slug === slug ? { ...item, qty: item.qty + delta } : item))
            .filter((item) => item.qty > 0);
        save(items);
        render();
    };

    const open = () => {
        lastFocus = document.activeElement;
        root.hidden = false;
        requestAnimationFrame(() => root.classList.add('is-open'));
        document.body.classList.add('is-locked');
        openers.forEach((b) => b.setAttribute('aria-expanded', 'true'));
        panel.focus();
    };

    const close = () => {
        root.classList.remove('is-open');
        document.body.classList.remove('is-locked');
        openers.forEach((b) => b.setAttribute('aria-expanded', 'false'));
        setTimeout(() => { root.hidden = true; }, 500);
        lastFocus?.focus();
    };

    openers.forEach((b) => b.addEventListener('click', open));
    $$('[data-cart-close]', root).forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !root.hidden) close();
    });

    $$('[data-cart-add]').forEach((button) => {
        const label = $('[data-cart-add-label]', button);
        const initial = label.textContent;
        button.addEventListener('click', () => {
            const { slug, name, title, image } = button.dataset;
            const existing = items.find((item) => item.slug === slug);
            if (existing) existing.qty += 1;
            else items.push({ slug, name, title, image, cake: button.hasAttribute('data-cake'), qty: 1 });
            save(items);
            render();

            button.classList.add('is-added');
            label.textContent = button.dataset.labelAdded || '✓';
            setTimeout(() => { button.classList.remove('is-added'); label.textContent = initial; }, 1600);
            counts.forEach((c) => { c.classList.remove('is-bump'); void c.offsetWidth; c.classList.add('is-bump'); });
            open();
        });
    });

    // On the order form, write the cart into the special instructions.
    const notes = $('[data-order-form] [name="notes"]');
    if (notes && new URLSearchParams(location.search).get('from') === 'cart' && items.length && !notes.value) {
        const lines = items.map((item) => `${item.qty} × ${item.title}${item.title !== item.name ? ` (${item.name})` : ''}`);
        notes.value = [notes.dataset.cartIntro || 'From my cart:', ...lines].join('\n').slice(0, notes.maxLength);
        notes.dispatchEvent(new Event('input', { bubbles: true }));
    }

    render();
}

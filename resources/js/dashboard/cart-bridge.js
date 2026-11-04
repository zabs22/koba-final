/*
 * "Reorder" from the customer dashboard: puts items into the public site's
 * existing cart (localStorage "koba:cart", same item shape as
 * resources/js/site/cart.js) without touching the cart code itself.
 */
const KEY = 'koba:cart';

export function addToCart(items) {
    let cart = [];
    try { cart = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { cart = []; }

    let added = 0;
    items.forEach((item) => {
        const existing = cart.find((i) => i.slug === item.slug);
        if (existing) existing.qty += item.qty;
        else cart.push({ ...item });
        added += item.qty;
    });

    try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) { return 0; }
    return added;
}

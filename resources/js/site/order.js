import { $, $$, reduceMotion } from './util';
import { validate, clearOnInput, send, applyServerErrors, setBusy, setFieldError } from './forms';
import { loadCart, saveCart } from './cart';

/** Multi-step order: details → order (the cart, editable) → request → review. */
export function initOrderForm() {
    const form = $('[data-order-form]');
    const steps = $$('[data-ostep]', form);
    const progress = $$('[data-progress]');
    const prev = $('[data-prev]', form);
    const next = $('[data-next]', form);
    const submit = $('[data-submit]', form);
    const stepOf = $('[data-step-of]', form);
    const formError = $('[data-form-error]', form);
    const success = $('[data-order-success]');
    const previews = $$('[data-preview]');
    const last = steps.length - 1;
    let current = 0;

    form.classList.add('is-enhanced');
    clearOnInput(form);

    const stepOfField = (name) => {
        if (name === 'items') return 1;
        const control = $(`[name="${name}"]`, form);
        return control ? steps.indexOf(control.closest('[data-ostep]')) : 0;
    };

    /* ---- Your Order: the cart, listed with quantity and size ------------- */
    const list = $('[data-order-items]', form);
    const empty = $('[data-order-empty]', form);
    const addTitle = $('[data-order-add-title]', form);
    const addHint = $('[data-order-add-hint]', form);
    const catalogue = JSON.parse(list.dataset.catalogue);
    const custom = JSON.parse(list.dataset.custom);
    const sizes = JSON.parse(list.dataset.sizes);
    const L = list.dataset;

    const el = (tag, cls, text) => {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text != null) node.textContent = text;
        return node;
    };
    const hidden = (name, value) => Object.assign(document.createElement('input'), { type: 'hidden', name, value });

    const update = (slug, patch) => {
        const items = loadCart()
            .map((item) => (item.slug === slug ? { ...item, ...patch } : item))
            .filter((item) => item.qty > 0);
        saveCart(items);
    };

    const addItem = (slug) => {
        const items = loadCart();
        const existing = items.find((item) => item.slug === slug);
        if (existing) existing.qty += 1;
        else items.push({ ...(slug === 'custom' ? custom : catalogue.find((c) => c.slug === slug)), qty: 1 });
        saveCart(items);
        setFieldError(form, 'items', '');
    };

    const renderItems = () => {
        const items = loadCart();
        list.replaceChildren(...items.map((item, i) => {
            const row = el('li', 'order-item');
            row.dataset.slug = item.slug;
            if (item.image) {
                const img = el('img', 'order-item__img');
                img.src = item.image;
                img.alt = '';
                row.append(img);
            } else {
                row.append(el('span', 'order-item__img order-item__img--blank'));
            }

            const body = el('div', 'order-item__body');
            body.append(el('p', 'order-item__title', item.title), el('p', 'order-item__name', item.name));

            if (item.cake) {
                const group = el('div', 'order-item__sizes');
                group.setAttribute('role', 'radiogroup');
                group.setAttribute('aria-label', `${L.labelSize}: ${item.title}`);
                Object.entries(sizes).forEach(([value, label]) => {
                    const pill = el('label', 'pill pill--sm');
                    const input = Object.assign(document.createElement('input'), { type: 'radio', name: `items[${i}][size]`, value, checked: item.size === value });
                    input.addEventListener('change', () => update(item.slug, { size: value }));
                    pill.append(input, el('span', null, label));
                    group.append(pill);
                });
                body.append(group);
            }

            const side = el('div', 'order-item__side');
            const qty = el('div', 'order-item__qty');
            const less = el('button', 'order-item__step', '−');
            less.type = 'button';
            less.setAttribute('aria-label', `${L.labelLess}: ${item.title}`);
            less.addEventListener('click', () => update(item.slug, { qty: item.qty - 1 }));
            const n = el('span', 'order-item__n', item.qty);
            n.setAttribute('aria-label', `${L.labelQty}: ${item.qty}`);
            const more = el('button', 'order-item__step', '+');
            more.type = 'button';
            more.setAttribute('aria-label', `${L.labelMore}: ${item.title}`);
            more.addEventListener('click', () => update(item.slug, { qty: Math.min(item.qty + 1, 50) }));
            qty.append(less, n, more);
            const remove = el('button', 'order-item__remove link', L.labelRemove);
            remove.type = 'button';
            remove.addEventListener('click', () => update(item.slug, { qty: 0 }));
            side.append(qty, remove);

            row.append(body, side,
                hidden(`items[${i}][slug]`, item.slug),
                hidden(`items[${i}][name]`, item.title !== item.name ? `${item.title} (${item.name})` : item.name),
                hidden(`items[${i}][qty]`, item.qty));
            return row;
        }));

        empty.hidden = items.length > 0;
        const hasCake = items.some((item) => item.cake);
        addTitle.textContent = hasCake ? addTitle.dataset.titleMore : addTitle.dataset.titleNone;
        addHint.hidden = hasCake;
        syncPreview();
    };

    const checkItems = () => {
        const items = loadCart();
        let message = '';
        if (!items.length) message = form.dataset.msgItems;
        else {
            const unsized = items.find((item) => item.cake && !item.size);
            if (unsized) message = form.dataset.msgSize.replace(':item', unsized.title);
        }
        setFieldError(form, 'items', message);
        return message ? ['items'] : [];
    };

    $$('[data-add-item]', form).forEach((button) => button.addEventListener('click', () => addItem(button.dataset.addItem)));
    window.addEventListener('koba:cart', renderItems);

    // "Order this cake" links: add that cake once.
    if (list.dataset.preselect && !loadCart().some((item) => item.slug === list.dataset.preselect)) addItem(list.dataset.preselect);

    const stepInvalid = (index) => {
        const invalid = validate(form, steps[index]);
        return index === 1 ? [...checkItems(), ...invalid] : invalid;
    };
    const focusInvalid = (name) => {
        if (name === 'items') $('[data-order-items-field]', form).scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
        else $(`[name="${name}"]`, form)?.focus();
    };

    const scrollToForm = () => {
        const top = form.getBoundingClientRect().top + window.scrollY - 140;
        if (Math.abs(window.scrollY - top) > 80) window.scrollTo({ top, behavior: reduceMotion ? 'auto' : 'smooth' });
    };

    const show = (index, focus = true) => {
        current = index;
        steps.forEach((step, i) => {
            step.hidden = i !== index;
            step.classList.toggle('is-current', i === index);
        });
        progress.forEach((item, i) => {
            item.classList.toggle('is-current', i === index);
            item.classList.toggle('is-done', i < index);
        });
        prev.hidden = index === 0;
        next.hidden = index === last;
        submit.hidden = index !== last;
        stepOf.textContent = form.dataset.msgStep.replace(':current', index + 1).replace(':total', steps.length);
        if (index === last) fillReview();
        if (focus) {
            const heading = $('.ostep__legend', steps[index]);
            heading.tabIndex = -1;
            heading.focus({ preventScroll: true });
            scrollToForm();
        }
    };

    const fillReview = () => {
        const text = (name) => {
            if (name === 'items') {
                return loadCart().map((item) => `${item.qty} × ${item.title}${item.size ? ` · ${sizes[item.size]}` : ''}`).join('\n');
            }
            const controls = $$(`[name="${name}"]`, form);
            const control = controls[0];
            if (control.type === 'radio') {
                const checked = controls.find((c) => c.checked);
                return checked ? checked.closest('label').textContent.trim() : '';
            }
            if (control.tagName === 'SELECT') return control.selectedOptions[0]?.value ? control.selectedOptions[0].textContent : '';
            return control.value.trim();
        };
        $$('[data-review-for]', form).forEach((dd) => { dd.textContent = text(dd.dataset.reviewFor) || dd.dataset.empty; });
    };

    next.addEventListener('click', () => {
        const invalid = stepInvalid(current);
        if (invalid.length) {
            focusInvalid(invalid[0]);
            return;
        }
        show(current + 1);
    });

    prev.addEventListener('click', () => show(current - 1));

    $$('[data-goto]', form).forEach((button) => {
        button.addEventListener('click', () => show(Number(button.dataset.goto)));
    });

    // Enter moves forward instead of submitting early.
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT' && current < last) {
            event.preventDefault();
            next.click();
        }
    });

    // The aside preview follows the chosen cake.
    function syncPreview() {
        const chosen = loadCart().find((item) => item.cake)?.slug || 'none';
        previews.forEach((node) => node.classList.toggle('is-active', node.dataset.preview === chosen));
    }
    renderItems();

    // Character count for the request.
    const notes = $('[name="notes"]', form);
    const count = $('[data-count-for="notes"]', form);
    const updateCount = () => { count.textContent = `${notes.value.length} / ${notes.maxLength}`; };
    notes.addEventListener('input', updateCount);
    updateCount();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        formError.hidden = true;

        for (let i = 0; i < last; i++) {
            const invalid = stepInvalid(i);
            if (invalid.length) {
                show(i);
                focusInvalid(invalid[0]);
                return;
            }
        }

        setBusy(submit, true, form.dataset.msgSending);
        try {
            const { ok, status, data } = await send(form);
            if (ok) {
                saveCart([]);
                form.hidden = true;
                progress.forEach((item) => { item.classList.remove('is-current'); item.classList.add('is-done'); });
                success.hidden = false;
                success.focus();
                scrollToForm();
                return;
            }
            if (status === 422) {
                const errors = { ...data.errors };
                Object.keys(errors).filter((key) => key.startsWith('items.')).forEach((key) => {
                    errors.items = errors.items || errors[key];
                    delete errors[key];
                });
                const first = applyServerErrors(form, errors);
                show(stepOfField(first));
                return;
            }
            throw new Error(String(status));
        } catch {
            formError.textContent = form.dataset.msgError;
            formError.hidden = false;
        } finally {
            setBusy(submit, false);
        }
    });

    // Server-side errors (no-JS round trip) open on the first failing step.
    const firstError = $$('[data-error-for]', form).find((el) => el.textContent.trim());
    show(firstError ? stepOfField(firstError.dataset.errorFor) : 0, false);
}

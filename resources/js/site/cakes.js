import { $, $$, pad, clamp, reduceMotion, finePointer, onFrame } from './util';

const desktop = window.matchMedia('(min-width: 900px)');

/* ---- The Signature Five: a 3D carousel stage ---------------------------- */

function initStage(stage) {
    const slides = $$('[data-ck-slide]', stage);
    const infos = $$('[data-ck-info]', stage);
    const ghosts = $$('[data-ck-ghost]', stage);
    const thumbs = $$('[data-ck-thumb]', stage);
    const scene = $('[data-ck-scene]', stage);
    const n = slides.length;
    let active = 0;

    const show = (index) => {
        active = (index + n) % n;
        slides.forEach((slide, i) => {
            // Shortest way round, so the ring wraps in both directions.
            let o = i - active;
            if (o > n / 2) o -= n;
            if (o < -n / 2) o += n;
            const a = Math.abs(o);
            slide.style.setProperty('--o', o);
            slide.style.setProperty('--a', a);
            slide.style.zIndex = 10 - a;
            slide.classList.toggle('is-active', a === 0);
            slide.classList.toggle('is-far', a > 2);
        });
        infos.forEach((info, i) => {
            info.hidden = i !== active;
            info.classList.toggle('is-active', i === active);
        });
        ghosts.forEach((ghost, i) => ghost.classList.toggle('is-active', i === active));
        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('is-active', i === active);
            thumb.setAttribute('aria-pressed', String(i === active));
        });
    };

    $('[data-ck-prev]', stage).addEventListener('click', () => show(active - 1));
    $('[data-ck-next]', stage).addEventListener('click', () => show(active + 1));
    thumbs.forEach((thumb, i) => thumb.addEventListener('click', () => show(i)));

    stage.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(active - 1);
        if (event.key === 'ArrowRight') show(active + 1);
    });

    // Swipe / drag on the scene; a plain click on a side cake brings it forward.
    let start = null;
    scene.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        start = { x: event.clientX, target: event.target.closest('[data-ck-slide]') };
        scene.setPointerCapture(event.pointerId);
        scene.classList.add('is-dragging');
    });
    const release = (event) => {
        if (!start) return;
        const dx = event.clientX - start.x;
        if (Math.abs(dx) > 50) show(active + (dx < 0 ? 1 : -1));
        else if (start.target) show(slides.indexOf(start.target));
        start = null;
        scene.classList.remove('is-dragging');
    };
    scene.addEventListener('pointerup', release);
    scene.addEventListener('pointercancel', () => {
        start = null;
        scene.classList.remove('is-dragging');
    });

    // Deep links from the menu: /cakes#cake-opera-caramel
    const fromHash = () => {
        const index = slides.findIndex((slide) => `#${slide.id}` === location.hash);
        if (index >= 0) {
            show(index);
            stage.scrollIntoView({ block: 'start' });
        }
    };
    window.addEventListener('hashchange', fromHash);

    show(0);
    fromHash();
}

/* ---- Anatomy: the cake separates into its layers as you scroll ----------- */

function initLayers(section) {
    const legend = $$('[data-ck-legend]', section);
    const pinned = () => !reduceMotion && desktop.matches;
    const sync = () => {
        section.classList.toggle('is-pinned', pinned());
        if (!pinned()) section.style.removeProperty('--e');
    };
    desktop.addEventListener('change', sync);
    sync();

    onFrame((vh) => {
        if (!pinned()) return;
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        const p = clamp(-rect.top / (rect.height - vh));
        section.style.setProperty('--e', clamp(p * 1.3).toFixed(3));
        const on = Math.ceil(p * (legend.length + 0.4));
        legend.forEach((item, i) => item.classList.toggle('is-on', i < on));
    });
}

/* ---- Pointer tilt with glare --------------------------------------------- */

function initTilt(el) {
    el.addEventListener('pointermove', (event) => {
        const rect = el.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        el.style.setProperty('--mx', ((x - 0.5) * 2).toFixed(3));
        el.style.setProperty('--my', ((y - 0.5) * 2).toFixed(3));
        el.style.setProperty('--gx', `${(x * 100).toFixed(1)}%`);
        el.style.setProperty('--gy', `${(y * 100).toFixed(1)}%`);
    });
    el.addEventListener('pointerleave', () => {
        el.style.setProperty('--mx', 0);
        el.style.setProperty('--my', 0);
    });
}

/* ---- By the slice: cards turn in 3D by their distance from centre -------- */

function initRail(rail) {
    const items = $$('[data-ck-rail-item]', rail);

    const update = () => {
        const centre = rail.scrollLeft + rail.clientWidth / 2;
        items.forEach((item) => {
            const width = item.offsetWidth || 1;
            const o = clamp((item.offsetLeft + width / 2 - centre) / width, -2, 2);
            const card = item.firstElementChild;
            card.style.setProperty('--o', o.toFixed(3));
            card.style.setProperty('--a', Math.abs(o).toFixed(3));
        });
    };

    rail.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
    window.addEventListener('resize', update);

    // Start on the middle slice so there is something either side.
    rail.scrollLeft = items[Math.floor(items.length / 2)].offsetLeft + items[0].offsetWidth / 2 - rail.clientWidth / 2;
    update();

    // Mouse drag (touch already scrolls natively).
    let drag = null;
    rail.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        drag = { x: event.clientX, left: rail.scrollLeft };
        rail.setPointerCapture(event.pointerId);
        rail.classList.add('is-dragging');
    });
    rail.addEventListener('pointermove', (event) => {
        if (drag) rail.scrollLeft = drag.left - (event.clientX - drag.x);
    });
    const release = () => {
        drag = null;
        rail.classList.remove('is-dragging');
    };
    rail.addEventListener('pointerup', release);
    rail.addEventListener('pointercancel', release);
}

/* ---- How ordering works: draw the line once it is seen ------------------- */

function initOrder(section) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
        section.classList.add('is-drawn');
        return;
    }
    new IntersectionObserver(([entry], observer) => {
        if (!entry.isIntersecting) return;
        section.classList.add('is-drawn');
        observer.disconnect();
    }, { threshold: 0.3 }).observe(section);
}

export function initCakes() {
    const stage = $('[data-ck-stage]');
    if (stage) initStage(stage);
    const layers = $('[data-ck-layers]');
    if (layers) initLayers(layers);
    if (finePointer && !reduceMotion) $$('[data-ck-tilt]').forEach(initTilt);
    const rail = $('[data-ck-rail]');
    if (rail) initRail(rail);
    const order = $('[data-ck-order]');
    if (order) initOrder(order);

    initLightbox();
}

/** Cake gallery lightbox built on the native <dialog> element. */
function initLightbox() {
    $$('[data-cake-dialog]').forEach((scope) => {
        const dialog = $('[data-lightbox-dialog]', scope);
        const img = $('[data-lightbox-img]', dialog);
        const caption = $('[data-lightbox-caption]', dialog);
        const count = $('[data-lightbox-count]', dialog);
        const triggers = $$('[data-lightbox]', scope);
        let index = 0;
        let opener = null;

        if (typeof dialog.showModal !== 'function') return;

        const render = (i) => {
            index = (i + triggers.length) % triggers.length;
            const trigger = triggers[index];
            img.classList.add('is-swapping');
            const next = new Image();
            next.onload = () => {
                img.src = next.src;
                img.alt = trigger.dataset.alt;
                img.classList.remove('is-swapping');
            };
            next.src = trigger.dataset.full;
            caption.textContent = trigger.dataset.alt;
            count.textContent = `${pad(index + 1)} / ${pad(triggers.length)}`;
        };

        triggers.forEach((trigger, i) => {
            trigger.addEventListener('click', () => {
                opener = trigger;
                render(i);
                dialog.showModal();
                document.body.classList.add('is-locked');
            });
        });

        $('[data-lightbox-prev]', dialog).addEventListener('click', () => render(index - 1));
        $('[data-lightbox-next]', dialog).addEventListener('click', () => render(index + 1));
        $('[data-lightbox-close]', dialog).addEventListener('click', () => dialog.close());

        dialog.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') render(index + 1);
            if (event.key === 'ArrowLeft') render(index - 1);
        });

        // Click on the backdrop closes.
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        dialog.addEventListener('close', () => {
            document.body.classList.remove('is-locked');
            opener?.focus();
        });
    });
}

import { $, $$, clamp, pad, reduceMotion } from './util';

/*
 * Guest-reviews carousel ("Heard at KOBA").
 *
 * The server renders one plain card per review (a native scroll-snap row when
 * JS is unavailable). Here the cards are placed on a seamless ring: each card
 * is translated individually along a looping position `p`, so the row never
 * resets or visibly jumps, however few reviews there are. Extra copies that
 * fill the ring are aria-hidden + inert; at any moment exactly one instance of
 * each review is exposed to assistive tech and the keyboard.
 */

const HOLD = 3200;           // ms each review rests during automatic progression
const mod = (a, m) => ((a % m) + m) % m;
const easeInOut = (t) => (t < 0.5 ? 4 * t * t * t : 1 - ((-2 * t + 2) ** 3) / 2);

export function initReviews() {
    $$('[data-reviews]').forEach(setup);
}

function setup(root) {
    const viewport = $('[data-reviews-viewport]', root);
    const track = $('[data-reviews-track]', root);
    const originals = $$('[data-review]', track);
    const n = originals.length;
    if (!n) return;

    const { labelMore, labelLess, labelPause, labelPlay, labelSlide } = root.dataset;
    const status = $('[data-reviews-status]', root);
    const current = $('[data-reviews-current]', root);
    const progress = $('[data-reviews-progress]', root);
    const prevBtn = $('[data-reviews-prev]', root);
    const nextBtn = $('[data-reviews-next]', root);
    const pauseBtn = $('[data-reviews-pause]', root);

    /* ---------- "Read more": same wording, only the clamp is lifted -------- */

    const measureMore = (list) => list.forEach((card) => {
        const text = $('[data-review-text]', card);
        const more = $('[data-review-more]', card);
        more.hidden = !card.classList.contains('is-open') && text.scrollHeight <= text.clientHeight + 1;
    });

    track.addEventListener('click', (event) => {
        const more = event.target.closest('[data-review-more]');
        if (!more) return;
        const card = more.closest('[data-review]');
        const open = !card.classList.contains('is-open');
        card.classList.toggle('is-open', open);
        more.setAttribute('aria-expanded', String(open));
        more.textContent = open ? labelLess : labelMore;
    });

    if (n < 2) {
        measureMore(originals);
        document.fonts?.ready.then(() => measureMore(originals));
        return;
    }

    /* ---------- Ring layout ------------------------------------------------ */

    root.classList.add('is-live');
    viewport.tabIndex = 0;

    let cards = originals;
    let copies = 1;
    let step = 1;                 // px between card origins
    let slots = n;                // cards on the ring
    let offsets = [];             // each card's offset from the active slot, in cards
    let p = 0;                    // ring position, in cards
    let shown = -1;
    let anim = null;

    const build = () => {
        const first = originals[0];
        step = first.getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);
        const wanted = Math.max(1, Math.ceil((Math.ceil(viewport.clientWidth / step) + 2) / n));
        if (wanted !== copies) {
            $$('[data-clone]', track).forEach((clone) => clone.remove());
            for (let j = n; j < n * wanted; j++) {
                const clone = originals[j % n].cloneNode(true);
                clone.dataset.clone = '';
                clone.style.setProperty('--i', j);
                track.append(clone);
            }
            copies = wanted;
            cards = $$('[data-review]', track);
        }
        slots = cards.length;
        measureMore(cards);
    };

    const render = () => {
        offsets = cards.map((card, j) => {
            const u = mod(j - p + 1, slots) - 1;   // [-1, slots - 1): one card waits just left of the active slot
            card.style.transform = `translate3d(${(u * step).toFixed(2)}px,0,0)`;
            const active = Math.abs(u) < 0.5;
            if (active !== card.classList.contains('is-active')) card.classList.toggle('is-active', active);
            return u;
        });
        const index = mod(Math.round(p), n);
        if (index !== shown) {
            shown = index;
            if (current) current.textContent = pad(index + 1);
            progress?.style.setProperty('--p', ((index + 1) / n).toFixed(3));
        }
    };

    /** Expose one instance of each review (the first one at or after the active slot). */
    const assignLive = () => {
        if (track.contains(document.activeElement)) return;
        const best = new Array(n).fill(-1);
        const score = (j) => (offsets[j] >= -0.5 ? offsets[j] : 1000 - offsets[j]);
        cards.forEach((_, j) => {
            const k = j % n;
            if (best[k] < 0 || score(j) < score(best[k])) best[k] = j;
        });
        cards.forEach((card, j) => {
            const live = best[j % n] === j;
            card.inert = !live;
            if (live) card.removeAttribute('aria-hidden'); else card.setAttribute('aria-hidden', 'true');
        });
    };

    /* ---------- Movement --------------------------------------------------- */

    const cancel = () => {
        if (anim) cancelAnimationFrame(anim.raf);
        anim = null;
    };

    const settle = () => {
        p = mod(p, slots);
        render();
        assignLive();
        schedule();
    };

    const goTo = (to) => {
        cancel();
        if (reduceMotion) {
            p = to;
            settle();
            return;
        }
        const a = { from: p, to, t0: performance.now(), dur: clamp(520 + (Math.abs(to - p) - 1) * 140, 520, 900), raf: 0 };
        const tick = (now) => {
            const t = clamp((now - a.t0) / a.dur);
            p = a.from + (a.to - a.from) * easeInOut(t);
            render();
            if (t < 1) a.raf = requestAnimationFrame(tick);
            else { anim = null; settle(); }
        };
        anim = a;
        a.raf = requestAnimationFrame(tick);
    };

    const announce = (index) => {
        if (status) status.textContent = labelSlide.replace(':n', index + 1).replace(':total', n);
    };

    const move = (direction, spoken) => {
        const to = (anim ? anim.to : Math.round(p)) + direction;
        goTo(to);
        if (spoken) announce(mod(to, n));
    };

    prevBtn?.addEventListener('click', () => move(-1, true));
    nextBtn?.addEventListener('click', () => move(1, true));
    root.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1, true); }
        if (event.key === 'ArrowRight') { event.preventDefault(); move(1, true); }
    });

    // Tabbing to a card that is out of view brings it into view (and undoes the browser's own scroll).
    root.addEventListener('focusin', (event) => {
        viewport.scrollLeft = 0;
        const card = event.target.closest?.('[data-review]');
        const j = card ? cards.indexOf(card) : -1;
        if (j < 0) return;
        const u = offsets[j];
        if (u < -0.4 || u > viewport.clientWidth / step - 0.6) goTo(Math.round(p + u));
    });

    /* ---------- Swipe / drag (vertical page scroll stays native) ----------- */

    let drag = null;
    let swallowClick = false;

    viewport.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        drag = { id: event.pointerId, x0: event.clientX, y0: event.clientY, p0: p, x: event.clientX, t: event.timeStamp, v: 0, on: false };
    });

    viewport.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        const dx = event.clientX - drag.x0;
        if (!drag.on) {
            if (Math.abs(dx) < 8 || Math.abs(dx) < Math.abs(event.clientY - drag.y0)) return;
            drag.on = true;
            cancel();
            drag.p0 = p;
            drag.x0 = event.clientX;
            viewport.setPointerCapture(event.pointerId);
            viewport.classList.add('is-dragging');
            hold('drag', true);
            return;
        }
        const dt = event.timeStamp - drag.t;
        if (dt > 0) drag.v = 0.7 * drag.v + 0.3 * ((event.clientX - drag.x) / dt);
        drag.x = event.clientX;
        drag.t = event.timeStamp;
        p = drag.p0 - (event.clientX - drag.x0) / step;
        render();
    });

    const release = (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        const { on, p0, v } = drag;
        drag = null;
        if (!on) return;
        viewport.classList.remove('is-dragging');
        swallowClick = true;
        setTimeout(() => { swallowClick = false; }, 60);
        const from = Math.round(p0);
        let to = Math.round(p - (v * 240) / step);
        if (to === from && Math.abs(p - p0) > 0.08) to += p > p0 ? 1 : -1;
        to = clamp(to, from - 2, from + 2);
        goTo(to);
        announce(mod(to, n));
        hold('drag', false);
    };
    viewport.addEventListener('pointerup', release);
    viewport.addEventListener('pointercancel', release);
    viewport.addEventListener('click', (event) => {
        if (!swallowClick) return;
        event.preventDefault();
        event.stopPropagation();
    }, true);
    viewport.addEventListener('dragstart', (event) => event.preventDefault());

    /* ---------- Gentle automatic progression ------------------------------- */

    const holds = new Set();
    let userPaused = false;
    let timer = null;

    const running = () => !reduceMotion && !userPaused && holds.size === 0;

    function schedule() {
        clearTimeout(timer);
        if (running() && !anim && !drag) timer = setTimeout(() => move(1, false), HOLD);
    }

    function hold(reason, on) {
        if (on) holds.add(reason); else holds.delete(reason);
        schedule();
    }

    if (!reduceMotion && pauseBtn) {
        pauseBtn.hidden = false;
        pauseBtn.addEventListener('click', () => {
            userPaused = !userPaused;
            root.classList.toggle('is-paused', userPaused);
            pauseBtn.setAttribute('aria-pressed', String(userPaused));
            pauseBtn.setAttribute('aria-label', userPaused ? labelPlay : labelPause);
            schedule();
        });
    }

    // Keeps sliding on its own: resting the mouse on the section or clicking a control doesn't stop it.
    // Only keyboard focus holds it (so it can be read and operated); the pause button stops it for good.
    root.addEventListener('focusin', (event) => { if (event.target.matches?.(':focus-visible')) hold('focus', true); });
    root.addEventListener('focusout', (event) => { if (!root.contains(event.relatedTarget)) hold('focus', false); });
    document.addEventListener('visibilitychange', () => hold('hidden', document.hidden));

    if ('IntersectionObserver' in window) {
        holds.add('offscreen');
        new IntersectionObserver(([entry]) => hold('offscreen', !entry.isIntersecting), { threshold: 0.1 }).observe(viewport);
    }

    /* ---------- Layout changes --------------------------------------------- */

    const relayout = () => {
        cancel();
        p = Math.round(p);
        build();
        settle();
    };

    let width = viewport.clientWidth;
    if ('ResizeObserver' in window) {
        new ResizeObserver(() => {
            if (viewport.clientWidth === width) return;
            width = viewport.clientWidth;
            relayout();
        }).observe(viewport);
    }
    document.fonts?.ready.then(() => measureMore(cards));

    build();
    render();
    assignLive();
    schedule();
}

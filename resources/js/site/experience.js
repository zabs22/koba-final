/*
 * Experience page (below the hero): the day journey, 3D tilt, the atmosphere
 * ring, the word-by-word hospitality quote and the craft strip.
 */
import { $, $$, clamp, reduceMotion, finePointer, onFrame } from './util';

const desktop = window.matchMedia('(min-width: 900px)');

/* ---- A day at KOBA -------------------------------------------------------
 * Vertical scroll drives a horizontal journey: on desktop, and on phones with
 * each act zoomed out to fit one screen and a short hold at every hour. On a
 * phone, tap an hour or swipe sideways to jump. With reduced motion, or on a
 * phone held sideways, the acts swipe instead.
 */

const tall = window.matchMedia('(min-height: 480px)');
const EASE_IN_OUT = 'cubic-bezier(0.65, 0, 0.35, 1)';

// Holds each act still for the first and last quarter of its stretch of scroll.
const hold = (p, span) => {
    const s = p * span;
    const i = Math.min(Math.floor(s), span - 1);
    const f = clamp((s - i - 0.25) / 0.5);
    return (i + f * f * (3 - 2 * f)) / span;
};

function initDay(section) {
    const sticky = $('.xp-day__sticky', section);
    const track = $('[data-xp-track]', section);
    const acts = $$('[data-xp-act]', section);
    const marks = $$('[data-xp-mark]', section);
    const arc = $('[data-xp-arc]', section);
    const svg = $('.xp-day__sky svg', section);
    const sun = $('[data-xp-sun]', section);
    const done = $('[data-xp-arc-done]', section);
    const length = arc.getTotalLength();
    const span = acts.length - 1;
    let current = -1;
    let drawn = 0;
    let screen = window.innerHeight;

    const pinned = () => !reduceMotion && (desktop.matches || tall.matches);
    const swiping = () => !desktop.matches && !pinned();

    // Each act's colour, so the sticky sky can blend between them mid-swipe.
    const tones = acts.map((act) => getComputedStyle(act).backgroundColor.match(/[\d.]+/g).map(Number));

    // The arc's stroke is non-scaling, so its dashes are measured on screen:
    // sample the stretched path to get its on-screen length.
    const measure = () => {
        const box = svg.getBoundingClientRect();
        let prev = null;
        drawn = 0;
        for (let i = 0; i <= 60; i++) {
            const pt = arc.getPointAtLength((i / 60) * length);
            const xy = [(pt.x / 1000) * box.width, (pt.y / 130) * box.height];
            if (prev) drawn += Math.hypot(xy[0] - prev[0], xy[1] - prev[1]);
            prev = xy;
        }
        done.style.strokeDasharray = `${drawn} ${drawn}`;
    };
    window.addEventListener('resize', measure);

    // p runs 0 → 1 from the first act to the last.
    const paint = (p) => {
        section.style.setProperty('--p', p.toFixed(4));
        if (!reduceMotion) acts.forEach((act, i) => act.style.setProperty('--lp', (p * span - i).toFixed(3)));

        // The sun follows the arc; the SVG is stretched, so map its units to pixels.
        const point = arc.getPointAtLength(p * length);
        const box = svg.getBoundingClientRect();
        sun.style.setProperty('--sx', `${(point.x / 1000) * box.width}px`);
        sun.style.setProperty('--sy', `${(point.y / 130) * box.height}px`);
        if (!drawn) measure();
        done.style.strokeDashoffset = (drawn * (1 - p)).toFixed(1);

        if (swiping()) {
            const at = p * span;
            const [from, to] = [tones[Math.floor(at)], tones[Math.ceil(at)]];
            const mix = from.slice(0, 3).map((c, k) => Math.round(c + (to[k] - c) * (at % 1)));
            section.style.setProperty('--sky-bg', `rgb(${mix.join(', ')})`);
        }

        const index = Math.round(p * span);
        if (index === current) return;
        current = index;
        section.dataset.act = index;
        marks.forEach((mark, i) => {
            mark.classList.toggle('is-on', i <= index);
            mark.classList.toggle('is-now', i === index);
        });
    };

    // Pinned on a phone, each act has one screen. The photos take the room the
    // words leave, up to about half of it; on a short screen the act shrinks to fit.
    const fit = () => {
        screen = sticky.offsetHeight || window.innerHeight;
        if (desktop.matches || !pinned()) {
            section.style.removeProperty('--stack-h');
            section.style.removeProperty('--fit');
            return;
        }
        const style = getComputedStyle(acts[0]);
        const area = acts[0].clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
        const copy = Math.max(...acts.map((act) => $('.xp-act__copy', act).offsetHeight));
        const gap = parseFloat(getComputedStyle($('.xp-act__grid', acts[0])).rowGap) || 0;
        const stack = Math.round(clamp(area - copy - gap, 160, Math.min(area * 0.48, 420)));
        section.style.setProperty('--stack-h', `${stack}px`);
        section.style.setProperty('--fit', Math.min(1, area / (stack + gap + copy)).toFixed(3));
    };
    window.addEventListener('resize', fit);
    document.fonts?.ready.then(fit);

    let queued = false;
    const swiped = () => {
        queued = false;
        if (!swiping()) return;
        const max = track.scrollWidth - track.clientWidth;
        paint(max > 0 ? clamp(track.scrollLeft / max) : 0);
    };
    const queueSwipe = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(swiped);
    };
    track.addEventListener('scroll', queueSwipe, { passive: true });
    window.addEventListener('resize', queueSwipe);

    const sync = () => {
        section.classList.toggle('is-pinned', pinned());
        if (!pinned()) {
            track.style.transform = '';
            acts.forEach((act) => act.style.removeProperty('--lp'));
        }
        fit();
        queueSwipe();
    };
    desktop.addEventListener('change', sync);
    tall.addEventListener('change', sync);
    sync();

    onFrame((vh) => {
        if (!pinned()) return;
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;

        const p = clamp(-rect.top / (rect.height - (desktop.matches ? vh : screen)));
        const at = desktop.matches ? p : hold(p, span);
        track.style.transform = `translate3d(${(-at * span * window.innerWidth).toFixed(1)}px, 0, 0)`;
        paint(at);
    });

    // Jump to an hour: slide the strip when swiping, scroll the page when pinned.
    const goTo = (i) => {
        if (swiping()) {
            track.scrollTo({ left: i * track.clientWidth, behavior: reduceMotion ? 'auto' : 'smooth' });
            return;
        }
        const top = section.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({ top: top + (i / span) * (section.offsetHeight - screen), behavior: 'smooth' });
    };
    marks.forEach((mark, i) => $('button', mark).addEventListener('click', () => {
        if (!desktop.matches) goTo(i);
    }));

    // Pinned on a phone, a sideways swipe still steps through the hours.
    let touch = null;
    sticky.addEventListener('touchstart', ({ touches }) => { touch = [touches[0].clientX, touches[0].clientY]; }, { passive: true });
    sticky.addEventListener('touchend', ({ changedTouches }) => {
        if (!touch || desktop.matches || swiping()) return;
        const dx = changedTouches[0].clientX - touch[0];
        const dy = changedTouches[0].clientY - touch[1];
        touch = null;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) goTo(clamp(current - Math.sign(dx), 0, span));
    }, { passive: true });

    acts.forEach((act) => initShuffle(act, section));
    initGyro(section);

    // Photos in the acts off to the side would otherwise wait for a swipe to start loading.
    new IntersectionObserver(([entry], observer) => {
        if (!entry.isIntersecting || desktop.matches) return;
        observer.disconnect();
        $$('img[loading="lazy"]', track).forEach((img) => { img.loading = 'eager'; });
    }, { rootMargin: '200% 0px' }).observe(section);

    // The first time the journey comes into view on a phone, nudge it to show it swipes.
    if (reduceMotion || !('IntersectionObserver' in window)) return;
    new IntersectionObserver(([entry], observer) => {
        if (!entry.isIntersecting || !swiping()) return;
        observer.disconnect();
        if (track.scrollLeft > 4) return;
        const nudge = acts.map((act) => act.animate(
            [{ translate: '0 0', easing: EASE_IN_OUT }, { translate: '-72px 0', offset: 0.45, easing: EASE_IN_OUT }, { translate: '0 0' }],
            { duration: 1400, delay: 350 },
        ));
        track.addEventListener('pointerdown', () => nudge.forEach((a) => a.cancel()), { once: true });
    }, { threshold: 0.4 }).observe(track);
}

/* ---- Small screens: tap the photos to deal them round ------------------- */

function initShuffle(act, section) {
    const stack = $('.xp-stack', act);
    const button = $('[data-xp-shuffle]', act);
    const layers = $$('.xp-stack__layer', stack);
    let busy = false;
    let flips = [];

    // Every frame turns edge-on, one after another.
    const flip = (from, to) => {
        const run = layers.map((layer, i) => layer.animate(
            [{ rotate: from }, { rotate: to }],
            { duration: 260, delay: i * 70, easing: EASE_IN_OUT, fill: 'both' },
        ));
        flips.push(...run);
        return Promise.all(run.map((a) => a.finished));
    };
    // Each frame takes the next frame's photo.
    const deal = () => {
        const images = layers.map((layer) => $('img', layer));
        layers.forEach((layer, i) => layer.append(images[(i + 1) % layers.length]));
    };

    const shuffle = async () => {
        if (busy || desktop.matches) return;
        section.classList.add('was-shuffled');
        if (reduceMotion) return deal();
        busy = true;
        $('svg', button).animate([{ rotate: '0deg' }, { rotate: '360deg' }], { duration: 760, easing: EASE_IN_OUT });
        await flip('y 0deg', 'y 90deg');
        deal();
        await flip('y -90deg', 'y 0deg');
        flips.forEach((a) => a.cancel());
        flips = [];
        busy = false;
    };
    stack.addEventListener('click', shuffle);
    button.addEventListener('click', shuffle);
}

/* ---- Small screens: tilting the phone moves the photo stacks ------------ */

function initGyro(section) {
    // iOS asks permission for motion; that prompt isn't worth it for a flourish.
    if (reduceMotion || !('DeviceOrientationEvent' in window) || typeof DeviceOrientationEvent.requestPermission === 'function') return;
    const stacks = $$('[data-xp-tilt]', section);
    let visible = false;
    let base = null;

    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(section);

    window.addEventListener('deviceorientation', (event) => {
        if (!visible || desktop.matches || event.beta === null) return;
        const now = [event.gamma, event.beta];
        // A slowly drifting baseline: however the phone is held, small tilts read as movement.
        base = base ? base.map((b, i) => b + (now[i] - b) * 0.02) : now;
        const mx = clamp((now[0] - base[0]) / 14, -1, 1).toFixed(3);
        const my = clamp((now[1] - base[1]) / 14, -1, 1).toFixed(3);
        stacks.forEach((stack) => {
            stack.style.setProperty('--mx', mx);
            stack.style.setProperty('--my', my);
        });
    });
}

/* ---- Pointer tilt: sets --mx / --my (−1…1) and the glare position -------- */

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

/* ---- Atmosphere: a 3D ring that drifts, follows scroll and can be dragged */

function initRing(section) {
    const stage = $('[data-xp-ring-stage]', section);
    const spin = $('[data-xp-ring-spin]', section);
    const cards = $$('[data-xp-ring-card]', section);
    const step = 360 / cards.length;
    let rot = 0;
    let velocity = 0;
    let scrollRot = 0;
    let drag = null;
    let visible = false;
    let frame = 0;

    const render = () => {
        const total = rot + scrollRot;
        spin.style.setProperty('--rot', `${total.toFixed(2)}deg`);
        cards.forEach((card, i) => {
            const facing = Math.cos(((i * step + total) * Math.PI) / 180);
            card.style.setProperty('--f', Math.max(0, facing).toFixed(3));
        });
    };

    const loop = () => {
        if (!drag) {
            velocity *= 0.94;
            rot += velocity + (reduceMotion ? 0 : -0.04);
        }
        render();
        if (visible) frame = requestAnimationFrame(loop);
    };

    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        cancelAnimationFrame(frame);
        if (visible) frame = requestAnimationFrame(loop);
    }).observe(stage);

    if (!reduceMotion) {
        onFrame(() => {
            if (visible) scrollRot = stage.getBoundingClientRect().top * 0.06;
        });
    }

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        drag = { x: event.clientX, last: event.clientX };
        stage.setPointerCapture(event.pointerId);
        stage.classList.add('is-dragging');
    });
    stage.addEventListener('pointermove', (event) => {
        if (!drag) return;
        const dx = event.clientX - drag.last;
        drag.last = event.clientX;
        rot += dx * 0.22;
        velocity = dx * 0.22;
    });
    const release = () => {
        drag = null;
        stage.classList.remove('is-dragging');
    };
    stage.addEventListener('pointerup', release);
    stage.addEventListener('pointercancel', release);

    stage.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        velocity = 0;
        rot = Math.round(rot / step) * step + (event.key === 'ArrowRight' ? -step : step);
        render();
    });

    render();
}

/* ---- Hospitality: the quote lights up word by word as it scrolls past ---- */

function initQuote(section) {
    const words = $$('.xp-word', section);
    if (reduceMotion) {
        words.forEach((w) => w.classList.add('is-lit'));
        return;
    }
    const text = $('.xp-quote__text', section);
    onFrame((vh) => {
        const rect = text.getBoundingClientRect();
        if (rect.bottom < -vh || rect.top > vh * 1.5) return;
        const p = clamp((vh * 0.85 - rect.top) / (vh * 0.55));
        const lit = Math.round(p * words.length);
        words.forEach((w, i) => w.classList.toggle('is-lit', i < lit));
    });
}

/* ---- Behind the counter: one craft step open at a time ------------------- */

function initCraft(section) {
    const steps = $$('[data-xp-step]', section);
    const open = (target) => steps.forEach((step) => {
        const on = step === target;
        step.classList.toggle('is-open', on);
        $('button', step).setAttribute('aria-expanded', String(on));
    });
    steps.forEach((step) => {
        const button = $('button', step);
        button.addEventListener('click', () => open(step));
        if (finePointer) button.addEventListener('pointerenter', () => open(step));
    });
}

export function initExperience() {
    const root = $('[data-xp]');
    if (!root) return;

    const day = $('[data-xp-day]', root);
    if (day) initDay(day);

    if (finePointer && !reduceMotion) $$('[data-xp-tilt]', root).forEach(initTilt);

    const ring = $('[data-xp-ring]', root);
    if (ring) initRing(ring);

    const quote = $('[data-xp-quote]', root);
    if (quote) initQuote(quote);

    const craft = $('[data-xp-craft]', root);
    if (craft) initCraft(craft);
}

/*
 * Experience page (below the hero): the day journey, 3D tilt, the atmosphere
 * ring, the word-by-word hospitality quote and the craft strip.
 */
import { $, $$, clamp, reduceMotion, finePointer, onFrame } from './util';

const desktop = window.matchMedia('(min-width: 900px)');

/* ---- A day at KOBA: vertical scroll drives a horizontal journey ---------- */

function initDay(section) {
    const track = $('[data-xp-track]', section);
    const acts = $$('[data-xp-act]', section);
    const marks = $$('[data-xp-mark]', section);
    const arc = $('[data-xp-arc]', section);
    const sky = $('.xp-day__sky', section);
    const sun = $('[data-xp-sun]', section);
    const done = $('[data-xp-arc-done]', section);
    const length = arc.getTotalLength();
    let current = -1;
    let drawn = 0;

    // The arc's stroke is non-scaling, so its dashes are measured on screen:
    // sample the stretched path to get its on-screen length.
    const measure = () => {
        const box = sky.getBoundingClientRect();
        let prev = null;
        drawn = 0;
        for (let i = 0; i <= 60; i++) {
            const pt = arc.getPointAtLength((i / 60) * length);
            const xy = [(pt.x / 1000) * box.width, (pt.y / 130) * 72];
            if (prev) drawn += Math.hypot(xy[0] - prev[0], xy[1] - prev[1]);
            prev = xy;
        }
        done.style.strokeDasharray = `${drawn} ${drawn}`;
    };
    window.addEventListener('resize', measure);

    const pinned = () => !reduceMotion && desktop.matches;
    const sync = () => {
        section.classList.toggle('is-pinned', pinned());
        if (!pinned()) {
            track.style.transform = '';
            acts.forEach((act) => act.style.removeProperty('--lp'));
        }
    };
    desktop.addEventListener('change', sync);
    sync();

    onFrame((vh) => {
        if (!pinned()) return;
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;

        const p = clamp(-rect.top / (rect.height - vh));
        const span = acts.length - 1;
        track.style.transform = `translate3d(${(-p * span * window.innerWidth).toFixed(1)}px, 0, 0)`;
        section.style.setProperty('--p', p.toFixed(4));
        acts.forEach((act, i) => act.style.setProperty('--lp', (p * span - i).toFixed(3)));

        // The sun follows the arc; the SVG is stretched, so map its units to pixels.
        const point = arc.getPointAtLength(p * length);
        const box = sky.getBoundingClientRect();
        sun.style.setProperty('--sx', `${(point.x / 1000) * box.width}px`);
        sun.style.setProperty('--sy', `${(point.y / 130) * 72}px`);
        if (!drawn) measure();
        done.style.strokeDashoffset = (drawn * (1 - p)).toFixed(1);

        const index = Math.round(p * span);
        if (index === current) return;
        current = index;
        section.dataset.act = index;
        marks.forEach((mark, i) => mark.classList.toggle('is-on', i <= index));
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

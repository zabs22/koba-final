/*
 * The cake box: the cart as a KOBA cake box, docked bottom-right (drawn in
 * partials/header.blade.php). Adding an item flies it into the box: the lid
 * swings open, the item drops into the mouth, the lid settles shut.
 * Same interface as the mesob it replaces (mesob.js), so cart.js can use either.
 */
import { $, root, clamp, reduceMotion, finePointer } from './util';

const NS = 'http://www.w3.org/2000/svg';
const CX = 80;          // centre of the box's mouth, in viewBox units (0 0 160 180)
const MOUTH_Y = 72;
const VIEW_W = 160;

const f = (v) => Math.round(v * 10) / 10;

function make(tag, attrs, parent) {
    const el = document.createElementNS(NS, tag);
    Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
    parent?.append(el);
    return el;
}

export function initCakebox({ total }) {
    const dock = $('[data-cakebox]');
    if (!dock) return null;
    const svg = $('[data-cakebox-art]', dock);
    const tilt = $('.cakebox__tilt', dock);
    const count = $('[data-cakebox-count]', dock);
    const mouth = $('[data-cakebox-mouth]', dock);
    const sparks = $('[data-cakebox-sparks]', dock);
    const toast = $('[data-mesob-toast]');
    const label = dock.getAttribute('aria-label');

    /* ---- Count and label -------------------------------------------------- */

    let flying = 0;
    const setCount = (bump) => {
        const n = total();
        count.textContent = n;
        count.hidden = n === 0;
        dock.setAttribute('aria-label', n ? `${label} (${n})` : label);
        if (bump) {
            count.classList.remove('is-bump');
            void count.offsetWidth;
            count.classList.add('is-bump');
        }
    };

    /* ---- Pointer tilt ------------------------------------------------------ */

    if (finePointer && !reduceMotion) {
        dock.addEventListener('pointermove', (event) => {
            const box = dock.getBoundingClientRect();
            const x = (event.clientX - box.left) / box.width - 0.5;
            const y = (event.clientY - box.top) / box.height - 0.5;
            tilt.style.setProperty('--rx', `${(-y * 18).toFixed(1)}deg`);
            tilt.style.setProperty('--ry', `${(x * 22).toFixed(1)}deg`);
        });
        dock.addEventListener('pointerleave', () => {
            tilt.style.setProperty('--rx', '0deg');
            tilt.style.setProperty('--ry', '0deg');
        });
    }

    /* ---- Arrival ----------------------------------------------------------- */

    dock.hidden = false;
    setCount(false);
    // A small "hello" once the page is ready: the lid lifts and settles.
    const arrive = () => {
        if (reduceMotion) return;
        setTimeout(() => {
            lid(1);
            setTimeout(() => lid(-1), 600);
        }, 1300);
    };

    /* ---- Toast ------------------------------------------------------------- */

    let toastTimer = 0;
    const announce = (title, image) => {
        if (!toast) return;
        $('[data-mesob-toast-title]', toast).textContent = title;
        const img = $('[data-mesob-toast-img]', toast);
        img.hidden = !image;
        if (image) img.src = image;
        toast.classList.add('is-on');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('is-on'), 2800);
    };

    /* ---- Dropping an item in ---------------------------------------------- */

    let open = 0;
    function lid(delta) {
        open = Math.max(0, open + delta);
        dock.classList.toggle('is-open', open > 0);
    }

    if (root.classList.contains('is-ready')) arrive();
    else {
        new MutationObserver((_, observer) => {
            if (!root.classList.contains('is-ready')) return;
            observer.disconnect();
            arrive();
        }).observe(root, { attributes: true, attributeFilter: ['class'] });
    }

    // Crumbs of light thrown up out of the box as the lid closes.
    const sparkle = () => {
        for (let i = 0; i < 7; i++) {
            const angle = Math.PI * (0.15 + (0.7 * i) / 6) + (Math.random() - 0.5) * 0.3;
            const dot = make('circle', { class: 'cb-spark', cx: CX + Math.cos(angle) * 26, cy: MOUTH_Y, r: 1.6 + Math.random() * 1.4, fill: i % 3 ? '#e6be7e' : '#fbf0d8' }, sparks);
            const dist = 26 + Math.random() * 22;
            dot.animate([
                { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                { transform: `translate(${Math.cos(angle) * dist * 0.6}px, ${-Math.sin(angle) * dist}px) scale(0.2)`, opacity: 0 },
            ], { duration: 700 + Math.random() * 300, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' }).onfinish = () => dot.remove();
        }
    };

    const land = (title, image) => {
        flying--;
        dock.classList.remove('is-landing');
        void dock.offsetWidth;
        dock.classList.add('is-landing');
        setTimeout(() => {
            lid(-1);
            if (!reduceMotion) sparkle();
        }, 160);
        if (!flying) setCount(true);
        announce(title, image);
    };

    // Inside the SVG: the item falls into the mouth and out of sight behind the front edges.
    const sink = (image, done) => {
        if (!image) return done();
        const piece = make('g', { class: 'cb-drop' }, mouth);
        const clip = `cb-round-${Date.now()}${Math.random().toString(36).slice(2, 6)}`;
        make('circle', { cx: CX, cy: MOUTH_Y - 4, r: 14 }, make('clipPath', { id: clip }, piece));
        make('image', { href: image, x: CX - 14, y: MOUTH_Y - 18, width: 28, height: 28, preserveAspectRatio: 'xMidYMid slice', 'clip-path': `url(#${clip})` }, piece);
        piece.animate([
            { transform: 'translateY(-4px) scale(1.05)' },
            { transform: 'translateY(46px) scale(0.8)' },
        ], { duration: 380, easing: 'cubic-bezier(0.5, 0, 0.9, 0.4)', fill: 'forwards' }).onfinish = () => {
            piece.remove();
            done();
        };
    };

    return {
        /** Refresh the count (held back while something is still on its way in). */
        sync() {
            if (!flying) setCount(false);
        },

        /** Fly the item from where it was added into the box. */
        drop(from, { title, image }) {
            flying++;
            lid(1);
            const finish = () => land(title, image);
            if (reduceMotion) {
                setTimeout(finish, 350);
                return;
            }

            const start = (from.closest('.menu-product')?.querySelector('.menu-product__media img') || from).getBoundingClientRect();
            const art = svg.getBoundingClientRect();
            const unit = art.width / VIEW_W;
            const size = 72;
            const x0 = start.left + start.width / 2;
            const y0 = start.top + start.height / 2;
            const x1 = art.left + CX * unit;
            const y1 = art.top + (MOUTH_Y - 4) * unit;
            const s0 = clamp(Math.min(start.width, start.height, 140) / size, 0.5, 2);
            const s1 = (28 * unit) / size;
            // A thrown arc: up and over, landing in the mouth.
            const cx = (x0 + x1) / 2;
            const cy = Math.min(y0, y1) - Math.max(120, Math.abs(x1 - x0) * 0.25);

            const fly = document.createElement('div');
            fly.className = 'mesob-fly';
            if (image) {
                const img = document.createElement('img');
                img.src = image;
                img.alt = '';
                fly.append(img);
            }
            document.body.append(fly);

            const frames = [];
            for (let i = 0; i <= 24; i++) {
                const u = i / 24;
                const x = (1 - u) ** 2 * x0 + 2 * (1 - u) * u * cx + u ** 2 * x1;
                const y = (1 - u) ** 2 * y0 + 2 * (1 - u) * u * cy + u ** 2 * y1;
                const s = s0 + (s1 - s0) * u + Math.sin(Math.PI * u) * 0.18;
                frames.push({ transform: `translate(${f(x - size / 2)}px, ${f(y - size / 2)}px) scale(${s.toFixed(3)}) rotate(${f(-24 * Math.sin(Math.PI * u))}deg)` });
            }
            fly.animate(frames, { duration: 860, easing: 'cubic-bezier(0.45, 0, 0.55, 1)', fill: 'forwards' }).onfinish = () => {
                fly.remove();
                sink(image, finish);
            };
        },
    };
}

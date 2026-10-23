/*
 * The mesob: the cart as a woven Ethiopian basket, docked bottom-right.
 * Drawn as SVG from three surfaces of revolution (lid, bowl, foot). The coil
 * stitches and zigzag bands are mapped onto those surfaces, so turning them
 * reads as the basket spinning in 3D. Adding an item flies it into the basket:
 * the lid lifts, the item drops through the mouth, the lid settles.
 */
import { $, root, clamp, reduceMotion, finePointer } from './util';

const NS = 'http://www.w3.org/2000/svg';
const CX = 80;          // centre line, in viewBox units (0 0 160 200)
const K = 0.2;          // ellipse squash: how far we look down onto the basket
const COLS = 48;        // pattern cells around the basket
const STITCHES = 64;    // coil stitches around the basket
const TAU = Math.PI * 2;
const RUST = '#a5472a';
const GREEN = '#0f5a3f';
const CREAM = '#fbf0d8';

const f = (v) => Math.round(v * 10) / 10;
const tri = (n, period) => {
    const m = ((n % period) + period) % period;
    return Math.min(m, period - m);
};

/* Each part's profile, from its top edge (t = 0) to its bottom edge (t = 1), and its woven bands. */
const PARTS = {
    // A tall hat: narrow spire, flaring brim (rows counted up from the brim).
    lid: {
        at: (t) => ({ y: 16 + 80 * t ** 0.85, r: 4.5 + 67.5 * t ** 2.1 }),
        fill: '#e7cc97',
        motif(row, col, rows) {
            const b = rows - 1 - row;
            if (b < 5) return Math.abs((col % 8) - 3.5) <= (4.6 - b) * 0.9 ? RUST : null;
            if (b === 6 || b === 14) return col % 2 ? null : CREAM;
            if (b >= 8 && b <= 11) return b - 8 === tri(col, 6) ? GREEN : null;
            if (b >= 21 && b <= 22) return RUST;
            return null;
        },
    },
    // The bowl that holds the food, curving in to the waist.
    bowl: {
        at: (t) => ({ y: 100 + 36 * t, r: 28 + 42 * Math.cos((t * Math.PI) / 2) ** 0.7 }),
        fill: '#e2c48e',
        motif(row, col) {
            if (row === 1) return RUST;
            if (row >= 3 && row <= 10) return row - 3 === tri(col, 12) || row - 4 === tri(col, 12) ? GREEN : null;
            if (row === 12) return col % 2 ? null : CREAM;
            if (row >= 13 && row <= 15) return tri(col, 6) + Math.abs(row - 14) <= 1 ? RUST : null;
            return null;
        },
    },
    // The flared stand.
    foot: {
        at: (t) => ({ y: 136 + 48 * t, r: 28 + 24 * t ** 1.5 }),
        fill: '#d9b981',
        motif(row, col) {
            if (row <= 4) return Math.abs((col % 6) - 2.5) <= (5 - row) * 0.55 ? RUST : null;
            if (row >= 7 && row <= 9) return row - 7 === tri(col, 4) ? GREEN : null;
            if (row === 12) return col % 2 ? null : CREAM;
            return null;
        },
    },
};

const point = (part, t, theta) => {
    const { y, r } = part.at(t);
    return [CX + r * Math.cos(theta), y + r * K * Math.sin(theta)];
};

/** Row boundaries spaced evenly along the profile, so every coil is the same height. */
function rowsOf(part, gap) {
    const fine = 200;
    const lens = [0];
    let prev = part.at(0);
    for (let i = 1; i <= fine; i++) {
        const p = part.at(i / fine);
        lens.push(lens[i - 1] + Math.hypot(p.r - prev.r, p.y - prev.y));
        prev = p;
    }
    const n = Math.max(2, Math.round(lens[fine] / gap));
    const ts = [];
    for (let k = 0, j = 0; k <= n; k++) {
        const want = (lens[fine] * k) / n;
        while (j < fine - 1 && lens[j + 1] < want) j++;
        ts.push(clamp((j + (want - lens[j]) / (lens[j + 1] - lens[j])) / fine));
    }
    return ts;
}

/** Silhouette: down the left, along the front of the bottom rim, up the right, then the top rim. */
function outline(part, topFront) {
    const left = [];
    const right = [];
    for (let i = 0; i <= 28; i++) {
        const { y, r } = part.at(i / 28);
        left.push(`${f(CX - r)} ${f(y)}`);
        right.unshift(`${f(CX + r)} ${f(y)}`);
    }
    const top = part.at(0);
    const bottom = part.at(1);
    return `M${left.join('L')}A${f(bottom.r)} ${f(bottom.r * K)} 0 0 0 ${f(CX + bottom.r)} ${f(bottom.y)}`
        + `L${right.join('L')}A${f(top.r)} ${f(top.r * K)} 0 0 ${topFront ? 1 : 0} ${f(CX - top.r)} ${f(top.y)}Z`;
}

const frontArc = (r, y, sweepBack = false) => `M${f(CX - r)} ${f(y)}A${f(r)} ${f(r * K)} 0 0 ${sweepBack ? 1 : 0} ${f(CX + r)} ${f(y)}`;

/** A leather band round the basket at radius r, from y0 down to y1. */
const band = (r, y0, y1) => `${frontArc(r, y0)}V${f(y1)}A${f(r)} ${f(r * K)} 0 0 1 ${f(CX - r)} ${f(y1)}Z`;

/** The front-facing part of the angle interval [a, b], or null when it's round the back. */
function front(a, b) {
    const shift = Math.floor(a / TAU) * TAU;
    a -= shift;
    b -= shift;
    if (a < Math.PI) return [a, Math.min(b, Math.PI)];
    if (b > TAU) return [0, Math.min(b - TAU, Math.PI)];
    return null;
}

function make(tag, attrs, parent) {
    const el = document.createElementNS(NS, tag);
    Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
    parent?.append(el);
    return el;
}

function draw(svg) {
    const defs = make('defs', {}, svg);
    defs.innerHTML = `
        <linearGradient id="mz-shade" x1="0" x2="1">
            <stop offset="0" stop-color="#2b1704" stop-opacity=".62"/>
            <stop offset=".2" stop-color="#2b1704" stop-opacity=".14"/>
            <stop offset=".36" stop-color="#fffaf0" stop-opacity=".24"/>
            <stop offset=".52" stop-color="#fffaf0" stop-opacity="0"/>
            <stop offset=".8" stop-color="#2b1704" stop-opacity=".24"/>
            <stop offset="1" stop-color="#2b1704" stop-opacity=".66"/>
        </linearGradient>
        <linearGradient id="mz-under" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0" stop-color="#2b1704" stop-opacity=".5"/>
            <stop offset=".4" stop-color="#2b1704" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="mz-leather" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0" stop-color="#1b6a4f"/>
            <stop offset="1" stop-color="#05291c"/>
        </linearGradient>
        <radialGradient id="mz-knob" cx=".35" cy=".35" r=".75">
            <stop offset="0" stop-color="#fbebc6"/>
            <stop offset="1" stop-color="#9c7840"/>
        </radialGradient>
        <radialGradient id="mz-inside" cx=".5" cy=".75" r=".65">
            <stop offset="0" stop-color="#5c3814"/>
            <stop offset="1" stop-color="#1e1007"/>
        </radialGradient>
        <radialGradient id="mz-light" cx=".5" cy=".7" r=".55">
            <stop offset="0" stop-color="#ffd98f" stop-opacity=".95"/>
            <stop offset="1" stop-color="#ffd98f" stop-opacity="0"/>
        </radialGradient>
        <clipPath id="mz-mouth"><path d="M10 100A70 14 0 0 0 150 100V-60H10Z"/></clipPath>`;

    const layers = {};
    const surface = (name, group, topFront) => {
        const part = PARTS[name];
        const ts = rowsOf(part, 3.2);
        make('path', { d: outline(part, topFront), fill: part.fill }, group);
        const paint = {
            [RUST]: make('path', { fill: RUST }, group),
            [GREEN]: make('path', { fill: GREEN }, group),
            [CREAM]: make('path', { fill: CREAM }, group),
            stitch: make('path', { fill: 'none', stroke: '#fff6e1', 'stroke-opacity': 0.5, 'stroke-width': 0.85, 'stroke-linecap': 'round' }, group),
        };
        make('path', {
            d: ts.slice(1, -1).map((t) => frontArc(part.at(t).r, part.at(t).y)).join(''),
            fill: 'none', stroke: '#4a2e10', 'stroke-opacity': 0.32, 'stroke-width': 0.6,
        }, group);
        make('path', { d: outline(part, topFront), fill: 'url(#mz-shade)' }, group);
        layers[name] = { part, ts, paint };
    };

    // Foot and bowl, with leather at the base and the waist.
    const body = make('g', { class: 'mz-body' }, svg);
    surface('foot', body, true);
    make('path', { d: band(52.4, 180, 186), fill: 'url(#mz-leather)' }, body);
    make('path', { d: frontArc(52.4, 183), fill: 'none', stroke: '#e6be7e', 'stroke-width': 0.8, 'stroke-dasharray': '2 2' }, body);

    // The mouth, seen when the lid lifts: back rim, a warm glow, whatever is dropping in.
    make('ellipse', { cx: CX, cy: 100, rx: 68, ry: 13.6, fill: 'url(#mz-inside)' }, body);
    make('ellipse', { class: 'mz-glow', cx: CX, cy: 102, rx: 60, ry: 12, fill: 'url(#mz-light)' }, body);
    make('path', { d: frontArc(70, 100, true), fill: 'none', stroke: 'url(#mz-leather)', 'stroke-width': 4 }, body);
    const mouth = make('g', { 'clip-path': 'url(#mz-mouth)' }, body);

    const bowl = make('g', {}, body);
    surface('bowl', bowl, true);
    make('path', { d: outline(PARTS.bowl, true), fill: 'url(#mz-under)' }, bowl);
    make('path', { d: band(29.5, 133, 139), fill: 'url(#mz-leather)' }, bowl);
    make('path', { d: frontArc(70, 100), fill: 'none', stroke: 'url(#mz-leather)', 'stroke-width': 4.5 }, bowl);
    const sparks = make('g', {}, bowl);

    // The lid, hinged at the right of the rim.
    const lid = make('g', { class: 'mz-lid' }, svg);
    surface('lid', lid, false);
    make('path', { d: band(72, 96, 102.5), fill: 'url(#mz-leather)' }, lid);
    make('path', { d: frontArc(72, 99.2), fill: 'none', stroke: '#e6be7e', 'stroke-width': 0.8, 'stroke-dasharray': '2.2 2.2' }, lid);
    make('ellipse', { cx: CX, cy: 17.5, rx: 5.6, ry: 1.8, fill: GREEN }, lid);
    make('circle', { cx: CX, cy: 12.5, r: 5.2, fill: 'url(#mz-knob)' }, lid);

    return { layers, mouth, sparks };
}

/** Lays the weave onto each surface at the given turn (0..1 = once round). */
function weave(layers, turn) {
    Object.values(layers).forEach(({ part, ts, paint }) => {
        const cells = { [RUST]: '', [GREEN]: '', [CREAM]: '' };
        let stitch = '';
        const rows = ts.length - 1;
        const spin = turn * TAU;
        const dc = TAU / COLS;
        const ds = TAU / STITCHES;

        for (let i = 0; i < rows; i++) {
            const t0 = ts[i];
            const t1 = ts[i + 1];

            for (let c = 0; c < COLS; c++) {
                const color = part.motif(i, c, rows);
                if (!color) continue;
                const span = front(spin + c * dc - 0.004, spin + (c + 1) * dc + 0.004);
                if (!span) continue;
                const [a, b] = span;
                const p = [point(part, t0, a), point(part, t0, b), point(part, t1, b), point(part, t1, a)];
                cells[color] += `M${p.map(([x, y]) => `${f(x)} ${f(y)}`).join('L')}Z`;
            }

            // Brick-laid stitches, slanting the way a coil is wrapped.
            const lift = (t1 - t0) * 0.2;
            for (let s = 0; s < STITCHES; s++) {
                const a = ((spin + (s + (i % 2) * 0.5) * ds) % TAU + TAU) % TAU;
                if (a < 0.06 || a > Math.PI - 0.12) continue;
                const [x0, y0] = point(part, t0 + lift, a);
                const [x1, y1] = point(part, t1 - lift, a + ds * 0.45);
                stitch += `M${f(x0)} ${f(y0)}L${f(x1)} ${f(y1)}`;
            }
        }

        paint[RUST].setAttribute('d', cells[RUST]);
        paint[GREEN].setAttribute('d', cells[GREEN]);
        paint[CREAM].setAttribute('d', cells[CREAM]);
        paint.stitch.setAttribute('d', stitch);
    });
}

export function initMesob({ total }) {
    const dock = $('[data-mesob]');
    if (!dock) return null;
    const svg = $('[data-mesob-art]', dock);
    const tilt = $('.mesob__tilt', dock);
    const count = $('[data-mesob-count]', dock);
    const toast = $('[data-mesob-toast]');
    const label = dock.getAttribute('aria-label');
    const { layers, mouth, sparks } = draw(svg);

    /* ---- Turning: momentum that decays, plus a slow drift while hovered --- */

    let turn = 0;
    let boost = 0;
    let hovering = false;
    let last = 0;
    let raf = 0;

    const tick = (now) => {
        const dt = Math.min(0.05, (now - (last || now)) / 1000);
        last = now;
        boost *= Math.exp(-dt * 2.6);
        const speed = boost + (hovering ? 0.08 : 0);
        turn = (turn + speed * dt) % 1;
        weave(layers, turn);
        raf = speed > 0.004 ? requestAnimationFrame(tick) : 0;
        if (!raf) last = 0;
    };
    const spin = (amount) => {
        if (reduceMotion) return;
        boost += amount;
        if (!raf) raf = requestAnimationFrame(tick);
    };
    weave(layers, 0);

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
        dock.addEventListener('pointerenter', () => {
            hovering = true;
            spin(0);
        });
        dock.addEventListener('pointermove', (event) => {
            const box = dock.getBoundingClientRect();
            const x = (event.clientX - box.left) / box.width - 0.5;
            const y = (event.clientY - box.top) / box.height - 0.5;
            tilt.style.setProperty('--rx', `${(-y * 18).toFixed(1)}deg`);
            tilt.style.setProperty('--ry', `${(x * 22).toFixed(1)}deg`);
        });
        dock.addEventListener('pointerleave', () => {
            hovering = false;
            tilt.style.setProperty('--rx', '0deg');
            tilt.style.setProperty('--ry', '0deg');
        });
    }

    /* ---- Arrival ----------------------------------------------------------- */

    dock.hidden = false;
    setCount(false);
    const arrive = () => setTimeout(() => spin(1.1), 700);
    if (root.classList.contains('is-ready')) arrive();
    else {
        new MutationObserver((_, observer) => {
            if (!root.classList.contains('is-ready')) return;
            observer.disconnect();
            arrive();
        }).observe(root, { attributes: true, attributeFilter: ['class'] });
    }

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
    const lid = (delta) => {
        open = Math.max(0, open + delta);
        dock.classList.toggle('is-open', open > 0);
    };

    // Crumbs of light thrown up out of the mouth as it lands.
    const sparkle = () => {
        for (let i = 0; i < 7; i++) {
            const angle = Math.PI * (0.15 + (0.7 * i) / 6) + (Math.random() - 0.5) * 0.3;
            const dot = make('circle', { class: 'mz-spark', cx: CX + Math.cos(angle) * 30, cy: 98, r: 1.6 + Math.random() * 1.4, fill: i % 3 ? '#e6be7e' : CREAM }, sparks);
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
        if (!reduceMotion) sparkle();
        spin(0.55);
        setTimeout(() => lid(-1), 160);
        if (!flying) setCount(true);
        announce(title, image);
    };

    // Inside the SVG: the item falls through the mouth and out of sight behind the front rim.
    const sink = (image, done) => {
        if (!image) return done();
        const piece = make('g', { class: 'mz-drop' }, mouth);
        const clip = `mz-round-${Date.now()}${Math.random().toString(36).slice(2, 6)}`;
        make('circle', { cx: CX, cy: 76, r: 13 }, make('clipPath', { id: clip }, piece));
        make('image', { href: image, x: CX - 13, y: 63, width: 26, height: 26, preserveAspectRatio: 'xMidYMid slice', 'clip-path': `url(#${clip})` }, piece);
        piece.animate([
            { transform: 'translateY(-4px) scale(1.05)' },
            { transform: 'translateY(48px) scale(0.8)' },
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

        /** Fly the item from where it was added into the basket. */
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
            const unit = art.width / 160;
            const size = 72;
            const x0 = start.left + start.width / 2;
            const y0 = start.top + start.height / 2;
            const x1 = art.left + CX * unit;
            const y1 = art.top + 76 * unit;
            const s0 = clamp(Math.min(start.width, start.height, 140) / size, 0.5, 2);
            const s1 = (26 * unit) / size;
            // A thrown arc: up and over, landing on the mouth.
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
            spin(0.25);
        },
    };
}

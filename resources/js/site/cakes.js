import { $, $$, pad, clamp, reduceMotion, finePointer, onFrame } from './util';

const desktop = window.matchMedia('(min-width: 900px)');

/* ---- The Signature Five: a 3D carousel stage ---------------------------- */
//eyob talents 
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
    const iso = $('.ck-iso', section);
    const chocolate = initChocolate(section);
    const pinned = () => !reduceMotion && desktop.matches;
    const sync = () => {
        section.classList.toggle('is-pinned', pinned());
        if (!pinned()) section.style.removeProperty('--e');
    };
    desktop.addEventListener('change', sync);
    sync();

    onFrame((vh) => {
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        if (!pinned()) {
            // Unpinned (small screens): pour as the cake itself crosses the viewport.
            const box = iso.getBoundingClientRect();
            chocolate?.pour(clamp(((vh - box.top) / (vh + box.height) - 0.12) / 0.5));
            return;
        }
        const p = clamp(-rect.top / (rect.height - vh));
        section.style.setProperty('--e', clamp(p * 1.3).toFixed(3));
        const on = Math.ceil(p * (legend.length + 0.4));
        legend.forEach((item, i) => item.classList.toggle('is-on', i < on));
        chocolate?.pour(clamp((p - 0.04) / 0.82));
    });
}

/* ---- Anatomy: the glaze runs, drips and pools as you scroll down ---------- */

const NS = 'http://www.w3.org/2000/svg';

const make = (tag, attrs, parent) => {
    const el = document.createElementNS(NS, tag);
    Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
    parent.append(el);
    return el;
};

// Seeded, so the drips run down the same places on every visit.
const seeded = (seed) => () => (seed = (seed * 16807) % 2147483647) / 2147483647;
const ramp = (value, from, to) => {
    const t = clamp((value - from) / (to - from));
    return t * t * (3 - 2 * t);
};
const r1 = (v) => Math.round(v * 10) / 10;
const blob = (cx, cy, r) => `M${r1(cx - r)} ${r1(cy)}a${r1(r)} ${r1(r)} 0 1 1 ${r1(2 * r)} 0a${r1(r)} ${r1(r)} 0 1 1 ${r1(-2 * r)} 0Z`;
const pill = (x, y, w, h) => {
    const r = r1(Math.min(w, h) / 2);
    [x, y, w, h] = [x, y, w, h].map(r1);
    return `M${x + r} ${y}H${x + w - r}A${r} ${r} 0 0 1 ${x + w} ${y + r}V${y + h - r}A${r} ${r} 0 0 1 ${x + w - r} ${y + h}`
        + `H${x + r}A${r} ${r} 0 0 1 ${x} ${y + h - r}V${y + r}A${r} ${r} 0 0 1 ${x + r} ${y}Z`;
};

/** Specular gloss lit from the bevel of `src`'s alpha: wet, rounded edges with a flat dark middle. */
function sheen(filter, src, azimuth, blur) {
    make('feGaussianBlur', { in: src, stdDeviation: blur, result: 'bump' }, filter);
    const light = make('feSpecularLighting', {
        in: 'bump', surfaceScale: 5, specularConstant: 1.1, specularExponent: 26, 'lighting-color': '#fff1e2', result: 'spec',
    }, filter);
    make('feDistantLight', { azimuth, elevation: 34 }, light);
    make('feComposite', { in: 'spec', in2: src, operator: 'in', result: 'shine' }, filter);
    make('feComposite', { in: src, in2: 'shine', operator: 'arithmetic', k1: 0, k2: 1, k3: 0.9, k4: 0 }, filter);
}

function initChocolate(section) {
    const world = $('.ck-iso__world', section);
    const glaze = $('.ck-iso__layer--glaze', section);
    const pool = $('[data-ck-pool]', section);
    // Light comes from the front-left, above: up-left on the front face, up-front on the left one.
    const faces = [['front', 225], ['left', 315]].map(([side, azimuth], i) => ({
        side,
        azimuth,
        seed: 20231 + i * 7919,
        plane: $(`[data-ck-drip="${side}"]`, section),
        fall: $(`[data-ck-fall="${side}"]`, section),
    }));
    if (!world || !glaze || !pool || faces.some((face) => !face.plane || !face.fall)) return null;

    let width = 0;  // side of the cake, px
    let edge = 12;  // glaze thickness, px
    let reach = 0;  // how far the pool box extends left of the cake
    let poolPath = null;
    let poolLast = '';
    let target = reduceMotion ? 0.7 : 0;
    let shown = target;
    let budget = 0;
    let flying = 0;
    let lastY = window.scrollY;
    let lastTime = 0;
    let raf = 0;
    let resized = false;
    window.addEventListener('resize', () => (resized = true), { passive: true });

    const build = () => {
        width = world.offsetWidth;
        edge = parseFloat(getComputedStyle(glaze).getPropertyValue('--t')) || 12;

        faces.forEach((face) => {
            const rand = seeded(face.seed);
            const count = Math.max(4, Math.floor(width / 40));
            const slot = width / count;
            // Slots at least 40px apart, so neighbouring drips and their fillets never touch.
            face.drips = Array.from({ length: count }, (_, i) => {
                const r = 2.6 + rand() * 2.6;
                return {
                    x: clamp((i + 0.5 + (rand() - 0.5) * 0.24) * slot, 20, width - 20),
                    r,
                    max: 8 + rand() * 40,
                    start: rand() * 0.35,
                    span: 0.4 + rand() * 0.35,
                    len: 0,
                    kick: -1e4,
                    landed: -1e4,
                };
            });
            // A few runs go long; those are the ones heavy enough to let go and fall.
            face.drips
                .map((drip) => [rand(), drip])
                .sort((a, b) => b[0] - a[0])
                .slice(0, width < 260 ? 2 : 3)
                .forEach(([, drip]) => Object.assign(drip, {
                    hero: true,
                    r: 4.2 + rand(),
                    max: 90 + rand() * 50,
                    start: 0.06 + rand() * 0.14,
                    span: 0.6,
                    splats: [0, 1].map(() => ({
                        along: (rand() < 0.5 ? -1 : 1) * (12 + rand() * 22),
                        out: 26 + rand() * 26,
                        r: 4 + rand() * 2.5,
                        at: 0.35 + rand() * 0.35,
                    })),
                }));

            const height = Math.ceil(edge + Math.max(...face.drips.map((drip) => drip.max)) + 40);
            face.plane.replaceChildren();
            const svg = make('svg', { width, height, viewBox: `0 0 ${width} ${height}` }, face.plane);
            const defs = make('defs', {}, svg);
            const fill = make('linearGradient', { id: `ck-choc-${face.side}`, gradientUnits: 'userSpaceOnUse', x1: 0, y1: 0, x2: 0, y2: height }, defs);
            make('stop', { offset: 0, 'stop-color': '#2c150a' }, fill);
            make('stop', { offset: 1, 'stop-color': '#130703' }, fill);
            const filter = make('filter', {
                id: `ck-sheen-${face.side}`, filterUnits: 'userSpaceOnUse', x: -10, y: -10, width: width + 20, height: height + 20, 'color-interpolation-filters': 'sRGB',
            }, defs);
            sheen(filter, 'SourceGraphic', face.azimuth, 2);
            face.body = make('path', { fill: `url(#ck-choc-${face.side})`, filter: `url(#ck-sheen-${face.side})` }, svg);
            face.streak = make('path', { fill: 'none', stroke: 'rgba(255, 238, 220, 0.2)', 'stroke-width': 1.4, 'stroke-linecap': 'round' }, svg);
            face.glint = make('path', { fill: 'rgba(255, 244, 232, 0.75)' }, svg);
            face.last = '';
        });

        // The pool: separate blobs melted together (goo), then given the same wet sheen.
        const size = pool.offsetWidth;
        reach = size - width;
        pool.querySelector('svg')?.remove();
        const svg = make('svg', { width: size, height: size, viewBox: `0 0 ${size} ${size}` }, pool);
        const filter = make('filter', {
            id: 'ck-pool', filterUnits: 'userSpaceOnUse', x: 0, y: 0, width: size, height: size, 'color-interpolation-filters': 'sRGB',
        }, make('defs', {}, svg));
        make('feGaussianBlur', { in: 'SourceGraphic', stdDeviation: 3.5, result: 'blur' }, filter);
        make('feColorMatrix', { in: 'blur', type: 'matrix', values: '1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 20 -8', result: 'goo' }, filter);
        sheen(filter, 'goo', 135, 2.5);
        poolPath = make('path', { fill: '#22100a', filter: 'url(#ck-pool)' }, svg);
        poolLast = '';
    };

    /** One frame of the run-off; returns true while something is still moving. */
    const render = (now) => {
        const dt = Math.min(64, now - (lastTime || now));
        lastTime = now;
        // Chocolate is viscous: the drips ease after the scroll instead of snapping to it.
        shown += (target - shown) * (1 - Math.exp(-dt / 110));
        if (Math.abs(target - shown) < 0.0005) shown = target;
        let busy = shown !== target;

        const lip = edge + 2.5 * shown;
        const sag = 3 * shown;
        const spread = 34 * ramp(shown, 0.15, 0.95);
        let pd = spread > 0.5
            ? pill(reach - spread, width - 6, width * 0.82 + spread, spread + 6) + pill(reach - spread, width * 0.18, spread + 6, width * 0.82 + spread)
            : '';

        faces.forEach((face) => {
            let body = `M0 0H${width}V${r1(lip)}`;
            let streak = '';
            let glint = '';
            let px = width;

            for (let i = face.drips.length - 1; i >= 0; i--) {
                const drip = face.drips[i];
                const { x, r } = drip;
                // A drop just let go: the tip snaps back and wobbles.
                const since = (now - drip.kick) / 1000;
                const recoil = since < 0.9 ? Math.exp(-since * 7) * Math.cos(since * 17) : 0;
                if (recoil) busy = true;
                const len = drip.len = Math.max(0, drip.max * ramp(shown, drip.start, drip.start + drip.span) - recoil * r * 2.6);

                if (drip.hero) {
                    const swell = Math.exp(-(now - drip.landed) / 240);
                    if (swell > 0.02) busy = true;
                    const lobe = (10 + drip.max * 0.16) * ramp(shown, drip.start + 0.2, 1) + 5 * swell;
                    const floor = (along, out) => (face.side === 'front' ? [reach + x + along, width + 2 + out] : [reach - 2 - out, x + along]);
                    if (lobe > 0.5) pd += blob(...floor(0, lobe * 0.5), lobe);
                    drip.splats.forEach((splat) => {
                        const size = splat.r * ramp(shown, drip.start + splat.at, drip.start + splat.at + 0.15);
                        if (size > 0.5) pd += blob(...floor(splat.along, splat.out), size);
                    });
                }

                if (len < 0.3) continue;
                // Stem, a heavier bulb at the tip, and soft fillets where it leaves the lip.
                // While short, everything is squashed vertically so the drip swells out of the edge.
                const w = r * (0.6 + 0.4 * clamp(len / (r * 7)));
                const b = w * (1 + 0.3 * clamp(len / 70));
                const fh = r * 1.5 + 1.5;
                const s = Math.min(1, len / (fh + 2.2 * b));
                const fv = fh * s;
                const by = b * s;
                const tip = lip + len;
                const neck = tip - 2.2 * by;
                Object.assign(drip, { tip, b, by });

                body += `Q${r1((px + x + w + fh) / 2)} ${r1(lip + sag)} ${r1(x + w + fh)} ${r1(lip)}`
                    + `C${r1(x + w + fh * 0.45)} ${r1(lip)} ${r1(x + w)} ${r1(lip + fv * 0.45)} ${r1(x + w)} ${r1(lip + fv)}`
                    + `L${r1(x + w * 0.85)} ${r1(neck)}`
                    + `C${r1(x + w * 0.85)} ${r1(tip - 1.6 * by)} ${r1(x + b)} ${r1(tip - 1.7 * by)} ${r1(x + b)} ${r1(tip - by)}`
                    + `A${r1(b)} ${r1(by)} 0 0 1 ${r1(x - b)} ${r1(tip - by)}`
                    + `C${r1(x - b)} ${r1(tip - 1.7 * by)} ${r1(x - w * 0.85)} ${r1(tip - 1.6 * by)} ${r1(x - w * 0.85)} ${r1(neck)}`
                    + `L${r1(x - w)} ${r1(lip + fv)}`
                    + `C${r1(x - w)} ${r1(lip + fv * 0.45)} ${r1(x - w - fh * 0.45)} ${r1(lip)} ${r1(x - w - fh)} ${r1(lip)}`;
                px = x - w - fh;

                if (neck - lip - fv > 6) streak += `M${r1(x - w * 0.42)} ${r1(lip + fv + 2)}V${r1(neck - 1)}`;
                if (len > 6) {
                    const gx = b * 0.2;
                    glint += `M${r1(x - b * 0.36 - gx)} ${r1(tip - by * 1.2)}a${r1(gx)} ${r1(by * 0.32)} 0 1 1 ${r1(2 * gx)} 0a${r1(gx)} ${r1(by * 0.32)} 0 1 1 ${r1(-2 * gx)} 0Z`;
                }
            }
            body += `Q${r1(px / 2)} ${r1(lip + sag)} 0 ${r1(lip)}Z`;

            if (body !== face.last) {
                face.last = body;
                face.body.setAttribute('d', body);
                face.streak.setAttribute('d', streak);
                face.glint.setAttribute('d', glint);
            }
        });

        if (pd !== poolLast) {
            poolLast = pd;
            poolPath.setAttribute('d', pd);
        }
        return busy;
    };

    const tick = (now) => {
        raf = 0;
        if (render(now)) kick();
    };
    const kick = () => {
        if (!raf) raf = requestAnimationFrame(tick);
    };

    const splash = (face, x) => {
        const [fx, fy] = face.side === 'front' ? [reach + x, width + 8] : [reach - 8, x];
        const ring = document.createElement('span');
        ring.className = 'ck-ripple';
        Object.assign(ring.style, { left: `${fx}px`, top: `${fy}px` });
        pool.append(ring);
        ring.animate([
            { transform: 'scale(0.1)', opacity: 0.9 },
            { transform: 'scale(1)', opacity: 0 },
        ], { duration: 760, easing: 'cubic-bezier(0.2, 0.7, 0.3, 1)', fill: 'forwards' }).onfinish = () => ring.remove();

        // A little crown thrown up the face as it lands.
        for (let i = 0; i < 4; i++) {
            const dot = document.createElement('span');
            dot.className = 'ck-splash';
            dot.style.left = `${x}px`;
            face.fall.append(dot);
            const dx = (Math.random() - 0.5) * 30;
            const up = 6 + Math.random() * 14;
            const size = 0.6 + Math.random() * 0.7;
            dot.animate([
                { transform: `translate(0, 0) scale(${size})`, easing: 'cubic-bezier(0.2, 0.6, 0.35, 1)' },
                { transform: `translate(${dx * 0.55}px, ${-up}px) scale(${size})`, offset: 0.42, easing: 'cubic-bezier(0.55, 0, 0.85, 0.35)' },
                { transform: `translate(${dx}px, 3px) scale(${size * 0.5})` },
            ], { duration: 420 + Math.random() * 160, fill: 'forwards' }).onfinish = () => dot.remove();
        }
    };

    /** Let the heaviest drip that's ready drop a bead of chocolate to the floor. */
    const release = () => {
        const now = performance.now();
        const ready = faces.flatMap((face) => face.drips
            .filter((drip) => drip.hero && drip.len > drip.max * 0.5 && now - drip.kick > 320)
            .map((drip) => [face, drip]));
        if (!ready.length || flying > 9) return;
        const [face, drip] = ready[Math.floor(Math.random() * ready.length)];
        drip.kick = now;

        // Height of the bulb above the floor, from where the glaze layer actually sits right now.
        const top = new DOMMatrixReadOnly(getComputedStyle(glaze).transform).m43 + edge;
        const h = Math.max(0, top - (drip.tip - drip.by));
        const b = drip.b * 1.15;
        const drop = document.createElement('span');
        drop.className = 'ck-drop';
        Object.assign(drop.style, { left: `${drip.x - b}px`, top: `${-h - b * 1.2}px`, width: `${b * 2}px`, height: `${b * 2.4}px` });
        face.fall.append(drop);
        flying++;

        // Real gravity: a quadratic ease-in over sqrt(2h/g), stretching as it speeds up.
        drop.animate([
            { transform: 'translateY(0) scale(1, 1)' },
            { transform: `translateY(${h + b * 0.4}px) scale(0.78, 1.4)` },
        ], { duration: Math.max(160, Math.sqrt((2 * h) / 0.0019)), easing: 'cubic-bezier(0.333, 0, 0.667, 0.333)', fill: 'forwards' }).onfinish = () => {
            drop.remove();
            flying--;
            drip.landed = performance.now();
            splash(face, drip.x);
            kick();
        };
    };

    build();
    kick();

    return {
        pour(value) {
            if (resized) {
                resized = false;
                if (world.offsetWidth !== width) build();
            }
            if (!reduceMotion) {
                target = value;
                // Drops only fall while scrolling down; scrolling back up just draws the chocolate back in.
                const y = window.scrollY;
                if (y > lastY && target > 0.2) budget = Math.min(budget + (y - lastY) / 46, 2.5);
                lastY = y;
                for (; budget >= 1; budget--) release();
            }
            kick();
        },
    };
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

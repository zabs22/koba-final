import { $, $$, clamp, pad, reduceMotion, finePointer, onFrame, queue } from './util';

/*
 * Home page, below the hero: scroll-driven chapters, 3D stages and the cake ring.
 * Every effect falls back to a static, readable layout without motion.
 */

const desktop = window.matchMedia('(min-width: 900px)');
const wide = window.matchMedia('(min-width: 1100px)');
const animated = () => !reduceMotion && desktop.matches;

/** Toggles `is-pinned` on a section when its scroll-driven mode applies. */
function pinWhen(section, test, onChange) {
    const sync = () => {
        section.classList.toggle('is-pinned', test());
        onChange?.();
        queue();
    };
    desktop.addEventListener('change', sync);
    wide.addEventListener('change', sync);
    sync();
}

/** Progress (0 → 1) through a pinned section: 0 as it reaches the top, 1 as it leaves. */
const pinProgress = (rect, vh) => clamp(-rect.top / Math.max(1, rect.height - vh));

/* ---- Chapter guide ------------------------------------------------------- */

function initChapters(flow) {
    const nav = $('[data-chapters]', flow);
    const list = $('[data-chapters-list]', nav);
    const no = $('[data-chapters-no]', nav);
    const name = $('[data-chapters-name]', nav);
    const sections = $$('[data-chapter]', flow);
    if (!nav || !sections.length) return;

    const links = sections.map((section, i) => {
        if (!section.id) section.id = `chapter-${i + 1}`;
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = `#${section.id}`;
        a.className = 'chapters__link';
        a.innerHTML = `<span class="chapters__dot"></span><span class="chapters__label"></span>`;
        a.lastElementChild.textContent = section.dataset.chapter;
        li.append(a);
        list.append(li);
        return a;
    });
    nav.hidden = false;

    let current = -1;
    onFrame((vh) => {
        const flowRect = flow.getBoundingClientRect();
        const visible = flowRect.top < vh * 0.45 && flowRect.bottom > vh * 0.55;
        nav.classList.toggle('is-visible', visible);
        if (!visible) return;

        let index = 0;
        sections.forEach((section, i) => {
            if (section.getBoundingClientRect().top <= vh * 0.5) index = i;
        });
        if (index === current) return;
        current = index;
        links.forEach((link, i) => {
            link.classList.toggle('is-active', i === index);
            link.classList.toggle('is-past', i < index);
            if (i === index) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
        nav.classList.toggle('is-on-dark', sections[index].dataset.chapterTone === 'dark');
        no.textContent = pad(index + 1);
        name.textContent = sections[index].dataset.chapter;
    });
}

/* ---- Rituals: morning → afternoon → evening ------------------------------ */

function initRituals(section) {
    const items = $$('[data-ritual]', section);
    const cards = $$('[data-ritual-card]', section);
    const n = cards.length;
    let step = -1;

    pinWhen(section, animated, () => {
        if (section.classList.contains('is-pinned')) return;
        section.removeAttribute('data-step');
        ['--p', '--sx', '--sy'].forEach((prop) => section.style.removeProperty(prop));
        cards.forEach((card) => ['--b', '--f'].forEach((prop) => card.style.removeProperty(prop)));
        items.forEach((item) => item.classList.remove('is-active', 'is-past'));
        step = -1;
    });

    onFrame((vh) => {
        if (!section.classList.contains('is-pinned')) return;
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;

        const p = pinProgress(rect, vh);
        const t = p * n;
        section.style.setProperty('--p', p.toFixed(4));

        // The sun on its arc: from the left horizon (morning) to the right (evening).
        const angle = Math.PI * (1 - (0.08 + p * 0.84));
        section.style.setProperty('--sx', ((300 + 280 * Math.cos(angle)) / 600).toFixed(4));
        section.style.setProperty('--sy', ((300 - 280 * Math.sin(angle)) / 320).toFixed(4));

        cards.forEach((card, i) => {
            const d = t - i;
            // Waiting cards sit behind, deeper the later they are; the front card lifts away near its end.
            card.style.setProperty('--b', clamp(-d, 0, n).toFixed(3));
            card.style.setProperty('--f', i < n - 1 ? clamp((d - 0.62) / 0.38).toFixed(3) : 0);
        });

        const now = Math.min(n - 1, Math.floor(t));
        if (now === step) return;
        step = now;
        section.dataset.step = now;
        items.forEach((item, i) => {
            item.classList.toggle('is-active', i === now);
            item.classList.toggle('is-past', i < now);
        });
    });
}

/* ---- Values: a horizontal track driven by vertical scroll ---------------- */

function initPillars(section) {
    const viewport = $('.pillars__viewport', section);
    const track = $('[data-pillars-track]', section);
    const cards = $$('[data-pillar]', section);
    const ticks = $$('[data-pillars-tick]', section);
    const count = $('[data-pillars-count]', section);
    let active = -1;

    const pinned = () => section.classList.contains('is-pinned');

    pinWhen(section, animated, () => {
        if (!pinned()) track.style.removeProperty('transform');
    });

    // Cards turn in 3D by their distance from the centre of the viewport.
    const place = (shift) => {
        const centre = viewport.clientWidth / 2;
        let best = 0;
        let bestDistance = Infinity;
        cards.forEach((card, i) => {
            const width = card.offsetWidth || 1;
            const o = (card.offsetLeft + width / 2 + shift - centre) / width;
            card.style.setProperty('--o', clamp(o, -3, 3).toFixed(3));
            card.style.setProperty('--a', Math.min(3, Math.abs(o)).toFixed(3));
            if (Math.abs(o) < bestDistance) {
                bestDistance = Math.abs(o);
                best = i;
            }
        });
        if (best === active) return;
        active = best;
        section.style.setProperty('--tone', cards[best].dataset.tone);
        count.textContent = pad(best + 1);
        ticks.forEach((tick, i) => {
            tick.classList.toggle('is-active', i === best);
            tick.classList.toggle('is-past', i < best);
        });
    };

    const centreOf = (card) => card.offsetLeft + card.offsetWidth / 2;

    onFrame((vh) => {
        const rect = section.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        if (pinned()) {
            const p = pinProgress(rect, vh);
            const first = centreOf(cards[0]);
            const last = centreOf(cards[cards.length - 1]);
            const shift = viewport.clientWidth / 2 - (first + (last - first) * p);
            track.style.transform = `translate3d(${shift.toFixed(1)}px, 0, 0)`;
            section.style.setProperty('--p', p.toFixed(4));
            place(shift);
        } else {
            place(-viewport.scrollLeft);
        }
    });

    viewport.addEventListener('scroll', queue, { passive: true });
}

/* ---- Craft: columns glide in, drift on scroll, full-size view ----------- */

function initCraft(section) {
    const wall = $('[data-craft-wall]', section);
    const tiles = $$('[data-craft-tile]', section);
    const figures = $$('[data-craft-open]', section).sort((a, b) => a.dataset.craftOpen - b.dataset.craftOpen);

    // 1 · Each tile glides in with its column as it enters the screen.
    if (reduceMotion || !('IntersectionObserver' in window)) {
        tiles.forEach((tile) => tile.classList.add('is-in'));
    } else {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                io.unobserve(entry.target);
                entry.target.classList.add('is-in');
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -8% 0px' });
        tiles.forEach((tile) => io.observe(tile));
    }

    // 2 · On wide screens the columns keep drifting at different speeds.
    const drifting = () => !reduceMotion && wide.matches;
    const sync = () => {
        section.classList.toggle('is-drifting', drifting());
        queue();
    };
    wide.addEventListener('change', sync);
    sync();
    onFrame((vh) => {
        if (!drifting()) return;
        const rect = wall.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        section.style.setProperty('--drift', clamp((rect.top + rect.height / 2 - vh / 2) / vh, -1, 1).toFixed(4));
    });

    // Hover: each photograph glides toward the pointer (mouse only).
    if (finePointer && !reduceMotion) {
        const RANGE = 11;
        figures.forEach((figure) => {
            const img = $('img', figure);
            figure.addEventListener('pointermove', (event) => {
                const rect = figure.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width - 0.5;
                const y = (event.clientY - rect.top) / rect.height - 0.5;
                img.style.setProperty('--px', `${(x * RANGE * 2).toFixed(1)}px`);
                img.style.setProperty('--py', `${(y * RANGE * 2).toFixed(1)}px`);
            });
            figure.addEventListener('pointerleave', () => {
                img.style.setProperty('--px', '0px');
                img.style.setProperty('--py', '0px');
            });
        });
    }

    // 3 · Full-size view, with previous / next and the arrow keys.
    const view = $('[data-craft-view]', section);
    if (!view || typeof view.showModal !== 'function') return;
    const viewImg = $('[data-craft-view-img]', view);
    let current = 0;

    const show = (index) => {
        current = (index + figures.length) % figures.length;
        const figure = figures[current];
        viewImg.src = figure.dataset.full;
        viewImg.alt = $('img', figure).alt;
        $('[data-craft-view-no]', view).textContent = `${pad(current + 1)} / ${pad(figures.length)}`;
        $('[data-craft-view-title]', view).textContent = figure.dataset.title;
        $('[data-craft-view-line]', view).textContent = figure.dataset.line;
        viewImg.classList.remove('is-swapping');
        void viewImg.offsetWidth;
        viewImg.classList.add('is-swapping');
        if (!view.open) view.showModal();
    };

    figures.forEach((figure, i) => figure.addEventListener('click', () => show(i)));
    $('[data-craft-view-prev]', view).addEventListener('click', () => show(current - 1));
    $('[data-craft-view-next]', view).addEventListener('click', () => show(current + 1));
    $('[data-craft-view-close]', view).addEventListener('click', () => view.close());
    view.addEventListener('click', (event) => { if (event.target === view) view.close(); });
    view.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });
    view.addEventListener('close', () => figures[current]?.focus());
}

/* ---- Menu preview: cards tilt toward the pointer ------------------------- */

function initTilt(scope) {
    if (!finePointer || reduceMotion) return;
    $$('.menu-product__media', scope).forEach((media) => {
        media.classList.add('is-tilt');
        media.addEventListener('pointermove', (event) => {
            const rect = media.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width;
            const y = (event.clientY - rect.top) / rect.height;
            media.style.setProperty('--rx', ((0.5 - y) * 10).toFixed(2));
            media.style.setProperty('--ry', ((x - 0.5) * 12).toFixed(2));
            media.style.setProperty('--gx', `${(x * 100).toFixed(1)}%`);
            media.style.setProperty('--gy', `${(y * 100).toFixed(1)}%`);
        });
        media.addEventListener('pointerleave', () => {
            media.style.setProperty('--rx', 0);
            media.style.setProperty('--ry', 0);
        });
    });
}

/* ---- Cakes: a 3D ring you can drag, spin, and step through --------------- */

function initRing(ring) {
    const scene = $('[data-ring-scene]', ring);
    const spin = $('[data-ring-spin]', ring);
    const faces = $$('[data-ring-face]', ring);
    const captions = $$('[data-ring-caption]', ring);
    const n = faces.length;
    const step = 360 / n;

    let angle = 0;
    let target = null;
    let velocity = 0;
    let drag = null;
    let dragged = false;
    let hovering = false;
    let idleSince = 0;
    let front = -1;
    let running = false;
    let last = 0;

    // Radius so neighbouring cards sit edge to edge, with a little air between.
    const measure = () => {
        const width = faces[0].offsetWidth;
        ring.style.setProperty('--r', `${Math.round((width / 2 / Math.tan(Math.PI / n)) * 1.22)}px`);
    };
    measure();
    window.addEventListener('resize', measure);

    const snap = (to) => {
        target = to;
        idleSince = performance.now();
    };
    const nearest = () => Math.round(angle / step) * step;

    const render = () => {
        spin.style.setProperty('--angle', `${angle.toFixed(3)}deg`);
        let best = 0;
        let bestFacing = -2;
        faces.forEach((face, i) => {
            const facing = Math.cos(((i * step + angle) * Math.PI) / 180);
            face.style.setProperty('--f', facing.toFixed(3));
            if (facing > bestFacing) {
                bestFacing = facing;
                best = i;
            }
        });
        const index = Number(faces[best].dataset.index);
        if (index === front) return;
        front = index;
        faces.forEach((face, i) => face.classList.toggle('is-front', i === best));
        captions.forEach((caption, i) => {
            caption.classList.toggle('is-active', i === index);
            $('a', caption).tabIndex = i === index ? 0 : -1;
        });
    };

    const tick = (now) => {
        const dt = Math.min(64, now - (last || now));
        last = now;
        if (drag) {
            // Position follows the pointer directly.
        } else if (target !== null) {
            angle += (target - angle) * Math.min(1, dt * 0.009);
            if (Math.abs(target - angle) < 0.02) {
                angle = target;
                target = null;
            }
        } else if (Math.abs(velocity) > 0.01) {
            angle += velocity;
            velocity *= 0.92;
            if (Math.abs(velocity) < 0.05) {
                velocity = 0;
                snap(nearest());
            }
        } else if (!reduceMotion && !hovering && now - idleSince > 3600) {
            // At rest, step calmly to the next cake.
            snap(nearest() - step);
        }
        render();
        if (running) requestAnimationFrame(tick);
    };

    const start = () => {
        if (running) return;
        running = true;
        last = 0;
        requestAnimationFrame(tick);
    };

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) start();
            else running = false;
        }).observe(ring);
    } else {
        start();
    }

    $('[data-ring-prev]', ring).addEventListener('click', () => snap((target ?? nearest()) + step));
    $('[data-ring-next]', ring).addEventListener('click', () => snap((target ?? nearest()) - step));
    ring.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') snap((target ?? nearest()) + step);
        if (event.key === 'ArrowRight') snap((target ?? nearest()) - step);
    });

    ring.addEventListener('pointerenter', () => { hovering = true; });
    ring.addEventListener('pointerleave', () => {
        hovering = false;
        idleSince = performance.now();
    });
    ring.addEventListener('focusin', () => { hovering = true; });
    ring.addEventListener('focusout', () => { hovering = false; });

    scene.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        drag = { x: event.clientX, start: event.clientX, id: event.pointerId };
        dragged = false;
        target = null;
        velocity = 0;
        scene.classList.add('is-dragging');
    });
    scene.addEventListener('pointermove', (event) => {
        if (!drag) return;
        const dx = event.clientX - drag.x;
        drag.x = event.clientX;
        if (!dragged && Math.abs(event.clientX - drag.start) > 6) {
            dragged = true;
            scene.setPointerCapture(drag.id);
        }
        if (!dragged) return;
        const delta = dx * 0.22;
        angle += delta;
        velocity = delta;
    });
    const release = () => {
        if (!drag) return;
        drag = null;
        scene.classList.remove('is-dragging');
        idleSince = performance.now();
        if (dragged && Math.abs(velocity) < 0.4) snap(nearest());
    };
    scene.addEventListener('pointerup', release);
    scene.addEventListener('pointercancel', release);

    // A drag never opens a link; a click on a side cake turns it to the front first.
    faces.forEach((face, i) => {
        $('a', face).addEventListener('click', (event) => {
            if (dragged) {
                event.preventDefault();
                dragged = false;
                return;
            }
            if (!face.classList.contains('is-front')) {
                event.preventDefault();
                let to = -i * step;
                to += Math.round((angle - to) / 360) * 360;
                snap(to);
            }
        });
    });

    render();
}

export function initHome() {
    const flow = $('[data-home]');
    if (!flow) return;

    initChapters(flow);
    const rituals = $('[data-rituals]', flow);
    if (rituals) initRituals(rituals);
    const pillars = $('[data-pillars]', flow);
    if (pillars) initPillars(pillars);
    const craft = $('[data-craft]', flow);
    if (craft) initCraft(craft);
    $$('[data-tilt-scope]', flow).forEach(initTilt);
    const ring = $('[data-ring]', flow);
    if (ring) initRing(ring);
}

/** The values track on its own, for pages outside the home flow (Our Story). */
export function initPillarsSection() {
    $$('[data-pillars]').forEach((section) => {
        if (!section.closest('[data-home]')) initPillars(section);
    });
}

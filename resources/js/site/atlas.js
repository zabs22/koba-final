/*
 * The KOBA Atlas — illustrated café map on the Locations page.
 * Fly-to zoom, drag to pan, live "open now" status in Addis Ababa time,
 * and an opt-in Google map (no third-party request until asked).
 */
import { $, $$, clamp, reduceMotion } from './util';

const MAP_W = 1000;
const MAP_H = 700;
const MIN_Z = 1;
const MAX_Z = 4;
const FOCUS_Z = 2.2;

const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

/** Current weekday and decimal hour in Addis Ababa, whatever the visitor's timezone. */
function addisNow() {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Africa/Addis_Ababa',
        weekday: 'short',
        hour: 'numeric',
        minute: 'numeric',
        hourCycle: 'h23',
    }).formatToParts(new Date());
    const get = (type) => parts.find((p) => p.type === type)?.value;
    return { day: get('weekday'), hour: Number(get('hour')) + Number(get('minute')) / 60 };
}

const isWeekend = (day) => day === 'Sat' || day === 'Sun';
const NEXT_DAY = { Sun: 'Mon', Mon: 'Tue', Tue: 'Wed', Wed: 'Thu', Thu: 'Fri', Fri: 'Sat', Sat: 'Sun' };

function formatHour(h) {
    const suffix = h >= 12 && h < 24 ? 'PM' : 'AM';
    return `${((h + 11) % 12) + 1}:00 ${suffix}`;
}

function status(open, labels) {
    const { day, hour } = addisNow();
    const [from, to] = open[isWeekend(day) ? 'weekend' : 'weekday'];
    if (hour >= from && hour < to) {
        return { open: true, text: `${labels.open_now} · ${labels.until.replace(':time', formatHour(to))}` };
    }
    const next = hour < from ? from : open[isWeekend(NEXT_DAY[day]) ? 'weekend' : 'weekday'][0];
    return { open: false, text: `${labels.closed} · ${labels.opens.replace(':time', formatHour(next))}` };
}

export function initAtlas() {
    $$('[data-atlas]').forEach((atlas) => {
        const labels = JSON.parse(atlas.dataset.labels || '{}');
        const stage = $('[data-atlas-stage]', atlas);
        const canvas = $('[data-atlas-canvas]', atlas);
        const items = $$('[data-atlas-item]', atlas);
        const pins = $$('[data-atlas-pin]', atlas);
        const cards = $$('[data-atlas-card]', atlas);
        const live = $('[data-atlas-live]', atlas);
        const coords = $('[data-atlas-coords]', atlas);
        const modes = $$('[data-atlas-mode]', atlas);
        const points = pins.map((pin) => [parseFloat(pin.style.getPropertyValue('--x')) / 100, parseFloat(pin.style.getPropertyValue('--y')) / 100]);

        let size = { sw: 0, sh: 0, cw: 0, ch: 0 };
        let view = { x: 0, y: 0, z: 1 };
        let focused = null;
        let active = 0;
        let tween = 0;

        /* ---- View transform ------------------------------------------------ */

        const bound = (v) => {
            const z = clamp(v.z, MIN_Z, MAX_Z);
            return {
                z,
                x: clamp(v.x, size.sw - size.cw * z, 0),
                y: clamp(v.y, size.sh - size.ch * z, 0),
            };
        };

        const apply = () => {
            canvas.style.transform = `translate3d(${view.x}px, ${view.y}px, 0) scale(${view.z})`;
            canvas.style.setProperty('--z', view.z.toFixed(3));
        };

        const go = (target, duration = 1100) => {
            cancelAnimationFrame(tween);
            target = bound(target);
            if (reduceMotion || !duration) {
                view = target;
                apply();
                return;
            }
            const from = { ...view };
            const start = performance.now();
            const step = (now) => {
                const t = ease(Math.min(1, (now - start) / duration));
                // Arc the zoom slightly so long hops feel like a flight.
                const lift = Math.sin(Math.PI * t) * Math.min(0.35, Math.abs(target.x - from.x) / size.sw) * from.z;
                view = bound({
                    x: from.x + (target.x - from.x) * t,
                    y: from.y + (target.y - from.y) * t,
                    z: Math.max(MIN_Z, from.z + (target.z - from.z) * t - lift),
                });
                apply();
                if (t < 1) tween = requestAnimationFrame(step);
            };
            tween = requestAnimationFrame(step);
        };

        const fit = (duration) => {
            focused = null;
            go({ z: 1, x: (size.sw - size.cw) / 2, y: (size.sh - size.ch) / 2 }, duration);
        };

        const focus = (index, duration) => {
            focused = index;
            const wide = size.sw >= 720;
            const [px, py] = points[index];
            const z = Math.max(view.z, FOCUS_Z);
            go({ z, x: size.sw * (wide ? 0.6 : 0.5) - px * size.cw * z, y: size.sh * (wide ? 0.42 : 0.36) - py * size.ch * z }, duration);
        };

        const measure = () => {
            const sw = stage.clientWidth;
            const sh = stage.clientHeight;
            const cw = Math.max(sw, sh * (MAP_W / MAP_H));
            size = { sw, sh, cw, ch: cw * (MAP_H / MAP_W) };
            canvas.style.width = `${size.cw}px`;
            canvas.style.height = `${size.ch}px`;
            focused === null ? fit(0) : focus(focused, 0);
        };

        /* ---- Live Google map (opt-in) ------------------------------------ */

        const embedFor = (index) => $('[data-atlas-embed]', cards[index]);

        const showLive = (on) => {
            modes.forEach((b) => {
                const pressed = (b.dataset.atlasMode === 'live') === on;
                b.classList.toggle('is-on', pressed);
                b.setAttribute('aria-pressed', String(pressed));
            });
            stage.classList.toggle('is-live', on);
            live.hidden = !on;
            if (on) loadLive();
        };

        const loadLive = () => {
            const link = embedFor(active);
            let frame = $('iframe', live);
            if (!frame) {
                frame = document.createElement('iframe');
                frame.loading = 'lazy';
                frame.referrerPolicy = 'no-referrer-when-downgrade';
                live.append(frame);
            }
            if (frame.src !== link.dataset.atlasEmbed) frame.src = link.dataset.atlasEmbed;
            frame.title = link.dataset.atlasTitle;
        };

        /* ---- Selection ----------------------------------------------------- */

        const activate = (index, { fly = true } = {}) => {
            active = index;
            items.forEach((item, i) => {
                item.classList.toggle('is-active', i === index);
                $('[data-atlas-pick]', item).setAttribute('aria-pressed', String(i === index));
            });
            pins.forEach((pin, i) => pin.classList.toggle('is-active', i === index));
            if (coords) coords.textContent = items[index].dataset.coords;
            cards.forEach((card, i) => {
                card.hidden = i !== index;
                card.classList.toggle('is-active', i === index);
            });
            if (stage.classList.contains('is-live')) loadLive();
            if (fly) focus(index);
        };

        const hover = (index, on) => {
            pins[index]?.classList.toggle('is-hover', on);
            items[index]?.classList.toggle('is-hover', on);
        };

        items.forEach((item, i) => {
            $('[data-atlas-pick]', item).addEventListener('click', () => activate(i));
            item.addEventListener('pointerenter', () => hover(i, true));
            item.addEventListener('pointerleave', () => hover(i, false));
        });
        pins.forEach((pin, i) => {
            pin.addEventListener('click', () => activate(i));
            pin.addEventListener('pointerenter', () => hover(i, true));
            pin.addEventListener('pointerleave', () => hover(i, false));
        });

        modes.forEach((b) => b.addEventListener('click', () => showLive(b.dataset.atlasMode === 'live')));

        $$('[data-atlas-zoom]', atlas).forEach((b) => b.addEventListener('click', () => {
            const z = clamp(view.z * (b.dataset.atlasZoom > 0 ? 1.5 : 1 / 1.5), MIN_Z, MAX_Z);
            const cx = size.sw / 2;
            const cy = size.sh / 2;
            focused = null;
            go({ z, x: cx - ((cx - view.x) * z) / view.z, y: cy - ((cy - view.y) * z) / view.z }, 600);
        }));
        $('[data-atlas-reset]', atlas)?.addEventListener('click', () => fit());

        /* ---- Drag to pan --------------------------------------------------- */

        let drag = null;
        stage.addEventListener('pointerdown', (event) => {
            if (event.button !== 0 || event.target.closest('button, a, iframe, .atlas__card')) return;
            cancelAnimationFrame(tween);
            drag = { id: event.pointerId, x: event.clientX - view.x, y: event.clientY - view.y };
            stage.setPointerCapture(event.pointerId);
            stage.classList.add('is-dragging');
        });
        stage.addEventListener('pointermove', (event) => {
            if (!drag || event.pointerId !== drag.id) return;
            focused = null;
            view = bound({ z: view.z, x: event.clientX - drag.x, y: event.clientY - drag.y });
            apply();
        });
        const endDrag = () => {
            drag = null;
            stage.classList.remove('is-dragging');
        };
        stage.addEventListener('pointerup', endDrag);
        stage.addEventListener('pointercancel', endDrag);

        /* ---- Open / closed status ----------------------------------------- */

        const refreshStatus = () => {
            items.forEach((item, i) => {
                const s = status(JSON.parse(item.dataset.open), labels);
                [item, cards[i]].forEach((scope) => $$('[data-atlas-status]', scope).forEach((el) => {
                    el.textContent = s.text;
                    el.classList.toggle('is-open', s.open);
                    el.hidden = false;
                }));
                pins[i].classList.toggle('is-closed', !s.open);
            });
        };
        refreshStatus();
        setInterval(refreshStatus, 60_000);

        /* ---- Boot ---------------------------------------------------------- */

        new ResizeObserver(measure).observe(stage);
        atlas.classList.add('is-ready');

        // Draw the trail and drop the pins the first time the map is seen.
        new IntersectionObserver((entries, observer) => {
            if (!entries[0].isIntersecting) return;
            atlas.classList.add('is-in');
            observer.disconnect();
        }, { threshold: 0.3 }).observe(stage);

        // Deep link: /locations#bole-atlas flies straight to that café.
        const fromHash = items.findIndex((item) => `#${item.dataset.slug}` === location.hash);
        if (fromHash > 0) activate(fromHash, { fly: false });
    });
}

import { json } from './http';

// Stop refining the GPS fix once it is this good, or after this long with any fix.
const GOOD_ENOUGH_M = 35;
const SETTLE_MS = 6000;
const GIVE_UP_MS = 15000;
// Hand-over to the form that sent the visitor here (resources/js/site/branch-finder.js).
const NEAREST_KEY = 'koba:nearest';

/** 640 → "640 m", 3092 → "3.1 km", 5 612 000 → "5,612 km". */
export const distance = (m) => (m < 950
    ? `${Math.max(10, Math.round(m / 10) * 10)} m`
    : `${(m / 1000).toLocaleString('en', { maximumFractionDigits: m < 10_000 ? 1 : 0 })} km`);

/** Seconds → "7 min" or "1 h 5 min" (never "0 min"). */
export const minutes = (s) => {
    const m = Math.max(1, Math.round(s / 60));
    return m < 60 ? `${m} min` : `${Math.floor(m / 60)} h ${m % 60} min`;
};

/*
 * Find the nearest café (/find-a-cafe): locate → rank → choose.
 * state: locating | ranking | results | far | error
 * The MapLibre map lives in a separate chunk (./locator-map) and outside
 * Alpine's reactivity; the panel works without it (no WebGL, tiles down).
 */
export default ({ urls, branches, purpose, returnTo, demo, t }) => {
    let map = null;
    let raw = null;          // last /nearest response, unproxied, for the map
    const routes = new Map(); // branch key → route, unproxied
    let watch = null;
    let timers = [];

    return {
        state: 'locating',
        error: null,
        fix: null,
        result: null,
        selected: null,
        threeD: true,
        mapReady: false,
        mapFailed: false,
        announcement: '',
        purpose,
        demo,
        t,

        init() {
            this.reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.loadMap();
            this.locate();
        },

        destroy() {
            this.stopWatching();
            map?.destroy();
        },

        async loadMap(retry = true) {
            try {
                const { createLocatorMap } = await import('./locator-map').catch((error) => {
                    // A dropped stylesheet fails the whole chunk on a shaky connection: clear it and try once more.
                    if (!retry) throw error;
                    document.querySelectorAll('link[href*="locator-map"]').forEach((link) => link.remove());
                    return new Promise((resolve) => { setTimeout(resolve, 1500); }).then(() => import('./locator-map'));
                });
                map = await createLocatorMap(this.$refs.map, {
                    style: urls.style,
                    branches,
                    reduced: this.reduced,
                    padding: () => this.padding(),
                    onPick: (key) => this.select(key),
                });
                this.mapReady = true;
                // Catch up with whatever happened while the map was loading.
                if (this.fix) map.showFix(this.fix);
                if (raw) {
                    map.showResult(raw, this.selected, { threeD: this.threeD });
                    if (routes.has(this.selected)) map.setRoute(this.selected, routes.get(this.selected));
                }
            } catch (error) {
                this.mapFailed = true;
            }
        },

        /* ---- 1. Where is the visitor? -------------------------------------- */

        locate() {
            this.stopWatching();
            Object.assign(this, { state: 'locating', error: null, result: null, selected: null });
            raw = null;
            routes.clear();

            if (!window.isSecureContext) return this.fail('insecure');
            if (!('geolocation' in navigator)) return this.fail('unsupported');

            let best = null;
            let finished = false;
            const started = Date.now();
            const finish = () => {
                if (finished) return;
                finished = true;
                this.stopWatching();
                best ? this.rank(best) : this.fail('timeout');
            };

            // Browsers answer fast from Wi-Fi first, then sharpen with GPS: keep the best fix.
            watch = navigator.geolocation.watchPosition((position) => {
                const fix = { lat: position.coords.latitude, lng: position.coords.longitude, accuracy: Math.round(position.coords.accuracy) };
                if (!best || fix.accuracy <= best.accuracy) {
                    best = fix;
                    this.fix = fix;
                    map?.showFix(fix);
                }
                if (best.accuracy <= GOOD_ENOUGH_M || Date.now() - started >= SETTLE_MS) finish();
            }, (error) => {
                if (best) return finish();
                finished = true;
                this.stopWatching();
                this.fail({ 1: 'denied', 3: 'timeout' }[error.code] ?? 'unavailable');
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: GIVE_UP_MS });

            timers.push(setTimeout(() => best && finish(), SETTLE_MS + 400), setTimeout(finish, GIVE_UP_MS + 400));
        },

        stopWatching() {
            if (watch !== null) navigator.geolocation.clearWatch(watch);
            watch = null;
            timers.forEach(clearTimeout);
            timers = [];
        },

        /** Demo only: a pretend location in Addis, for presenting from abroad. */
        useDemo() {
            this.stopWatching();
            const fix = { lat: demo[0], lng: demo[1], accuracy: 20 };
            this.fix = fix;
            map?.showFix(fix);
            this.rank(fix);
        },

        fail(reason) {
            this.error = reason;
            this.state = 'error';
            this.say(t.errors[reason]?.[0] ?? '');
        },

        /* ---- 2. Rank the cafés (server: distance → roads → traffic) ---------- */

        async rank(fix) {
            this.state = 'ranking';
            try {
                raw = await json(urls.nearest, { method: 'POST', body: { lat: fix.lat, lng: fix.lng, accuracy: fix.accuracy, for: purpose } });
            } catch (e) {
                return this.fail('network');
            }
            this.result = raw;
            this.selected = raw.recommended;
            this.state = raw.in_area ? 'results' : 'far';
            map?.showResult(raw, this.selected, { threeD: this.threeD });
            this.loadRoute(this.selected);

            const best = this.picked;
            this.say(raw.in_area
                ? `${t.nearest}: ${best.name}, ${minutes(best.drive_s)} ${t.by_car}, ${distance(best.road_m)}.`
                : t.far.replace(':distance', distance(raw.branches[0].straight_m)));
        },

        /* ---- 3. Look at another café, then use one ---------------------------- */

        select(key) {
            this.selected = key;
            if (!raw?.in_area) {
                map?.focusBranch(key);
                return;
            }
            // The camera starts at once; the road line follows when it arrives.
            map?.showSelection(key, routes.get(key) ?? null, { threeD: this.threeD });
            this.loadRoute(key);
        },

        /** The road line to one café (dashed straight line until then). One retry after a pause. */
        async loadRoute(key, retry = true) {
            if (!raw?.in_area || routes.has(key)) return;
            let route = null;
            try {
                ({ route } = await json(urls.route, { method: 'POST', body: { lat: this.fix.lat, lng: this.fix.lng, branch: key } }));
            } catch (e) { /* stays dashed */ }
            if (!route && retry) {
                await new Promise((resolve) => { setTimeout(resolve, 1300); });
                return this.loadRoute(key, false);
            }
            if (!route) return;
            routes.set(key, route);
            map?.setRoute(key, route);
        },

        use(key) {
            const b = this.branch(key);
            try {
                sessionStorage.setItem(NEAREST_KEY, JSON.stringify({
                    branch: key, name: b.name, short: b.short, located: Boolean(raw?.in_area),
                    drive_s: b.drive_s ?? null, road_m: b.road_m ?? null, estimated: b.estimated ?? true, at: Date.now(),
                }));
            } catch (e) { /* the form still gets ?branch= */ }
            const url = new URL(returnTo, window.location.origin);
            url.searchParams.set('branch', key);
            window.location.assign(url.toString());
        },

        toggle3D() {
            this.threeD = !this.threeD;
            map?.setThreeD(this.threeD, this.selected);
        },

        recenter() {
            map?.frame(this.selected, { threeD: this.threeD });
        },

        /* ---- What the panel shows ------------------------------------------- */

        branch(key) {
            return raw?.branches.find((b) => b.key === key) ?? branches.find((b) => b.key === key);
        },

        get picked() {
            return this.selected ? this.branch(this.selected) : null;
        },

        /** The café the panel lists below the main card. */
        get rows() {
            const list = this.result?.branches ?? branches;
            return this.state === 'results' ? list.filter((b) => b.key !== this.selected) : list;
        },

        /** The closest café was passed over because it isn't taking pre-orders. */
        get skipped() {
            const r = this.result;
            return r && r.in_area && r.nearest !== r.recommended && this.selected === r.recommended ? this.branch(r.nearest) : null;
        },

        get canUse() {
            return Boolean(this.picked) && (purpose !== 'order' || this.picked.takes_orders);
        },

        eta(b) { return b?.drive_s ? minutes(b.drive_s) : ''; },
        road(b) { return b?.road_m ? distance(b.road_m) : ''; },
        walkTime(b) { return b?.walk_s ? minutes(b.walk_s) : ''; },
        away(b) { return b?.straight_m ? t.direction.replace(':distance', distance(b.straight_m)).replace(':compass', t.compass[b.compass] ?? b.compass) : b?.area; },
        accuracy() { return this.fix ? t.accuracy.replace(':distance', distance(this.fix.accuracy)) : ''; },
        /** 0–100: how sharp the fix is, for the little meter (5 km → 0, 10 m → 100). */
        sharpness() { return this.fix ? Math.max(4, Math.min(100, 100 - (Math.log10(Math.max(10, this.fix.accuracy)) - 1) / 2.7 * 100)) : 4; },

        /** Map padding so the route is never hidden behind the panel. */
        padding() {
            const panel = this.$refs.panel.getBoundingClientRect();
            return matchMedia('(min-width: 1024px)').matches
                ? { top: 96, bottom: 72, left: panel.right + 48, right: 96 }
                : { top: 96, bottom: window.innerHeight - panel.top + 36, left: 44, right: 44 };
        },

        say(text) {
            this.announcement = '';
            this.$nextTick(() => { this.announcement = text; });
        },
    };
};

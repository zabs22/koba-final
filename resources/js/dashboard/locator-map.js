/*
 * The 3D map for "Find the nearest café": MapLibre GL over OpenFreeMap tiles,
 * recoloured to KOBA's night palette (the look of the Atlas on the Locations
 * page), with 3D buildings, the visitor's dot and accuracy halo, the hexagon
 * cells around them, KOBA pins, and the route drawn from the visitor to the café.
 * Loaded on this page only, as its own chunk.
 */
import { Map as MapLibre, Marker, LngLatBounds, setWorkerUrl } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';

setWorkerUrl(workerUrl);

const C = {
    night: '#03231a', land: '#072f24', block: '#0a3a2c', park: '#0b4a36', water: '#0b3d38', river: '#3d8c7c',
    casing: '#021b14', minor: 'rgba(255, 245, 230, 0.15)', path: 'rgba(255, 245, 230, 0.08)',
    major: 'rgba(230, 190, 126, 0.42)', motorway: 'rgba(230, 190, 126, 0.72)', faint: 'rgba(255, 245, 230, 0.12)',
    label: 'rgba(255, 245, 230, 0.66)', place: '#f2d4a7', waterLabel: '#7cc0b2', sand: '#e6be7e', you: '#3d8bfd',
};

/** Farther than this from every café, don't follow the visitor's dot (they're not in Addis). */
const FOLLOW_KM = 80;

const empty = () => ({ type: 'FeatureCollection', features: [] });
const line = (coordinates) => ({ type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates } });
const toRad = (d) => (d * Math.PI) / 180;

function km([lng1, lat1], [lng2, lat2]) {
    const a = Math.sin(toRad(lat2 - lat1) / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(toRad(lng2 - lng1) / 2) ** 2;
    return 12742 * Math.asin(Math.min(1, Math.sqrt(a)));
}

function bearing([lng1, lat1], [lng2, lat2]) {
    const y = Math.sin(toRad(lng2 - lng1)) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(toRad(lng2 - lng1));
    return (Math.atan2(y, x) * 180) / Math.PI;
}

/** A circle of `metres` around [lng, lat], for the accuracy halo. */
function circle([lng, lat], metres, steps = 72) {
    const dLat = metres / 110_574;
    const dLng = metres / (111_320 * Math.cos(toRad(lat)));
    const ring = Array.from({ length: steps + 1 }, (_, i) => {
        const a = (i / steps) * 2 * Math.PI;
        return [lng + dLng * Math.cos(a), lat + dLat * Math.sin(a)];
    });
    return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [ring] } };
}

function roadColor(id) {
    if (id.includes('casing')) return C.casing;
    if (id.includes('motorway')) return C.motorway;
    if (id.includes('major')) return C.major;
    if (id.includes('rail')) return C.faint;
    if (id.includes('path') || id.includes('pier')) return C.path;
    return C.minor;
}

/** OpenFreeMap's Positron, recoloured: forest land, sand roads, teal water, cream labels. */
function brand(style) {
    delete style.sources.ne2_shaded;
    style.layers = style.layers.filter((layer) => layer.source !== 'ne2_shaded');

    for (const layer of style.layers) {
        const source = layer['source-layer'];
        const paint = (layer.paint ??= {});
        if (layer.type === 'background') {
            paint['background-color'] = C.land;
        } else if (layer.type === 'fill') {
            delete paint['fill-outline-color'];
            paint['fill-color'] = { water: C.water, park: C.park, landcover: C.park, building: '#0d4a36' }[source] ?? C.block;
            paint['fill-opacity'] = { park: 0.6, landcover: 0.45, landuse: 0.55, building: 0.8 }[source] ?? 1;
        } else if (layer.type === 'line') {
            paint['line-color'] = { waterway: C.river, boundary: 'rgba(255, 245, 230, 0.16)', aeroway: C.faint }[source] ?? roadColor(layer.id);
        } else if (layer.type === 'symbol') {
            if (!layer.layout?.['text-field'] || layer.id.includes('shield')) {
                (layer.layout ??= {}).visibility = 'none';
                continue;
            }
            paint['text-color'] = source === 'place' ? C.place : ['water_name', 'waterway'].includes(source) ? C.waterLabel : C.label;
            paint['text-halo-color'] = C.night;
            paint['text-halo-width'] = 1.4;
        }
    }

    // A dusk sky over the horizon when the map is tilted, and warm light on the buildings.
    style.sky = { 'sky-color': '#0b4a36', 'horizon-color': '#d8b06d', 'fog-color': '#072f24', 'sky-horizon-blend': 0.55, 'horizon-fog-blend': 0.4, 'fog-ground-blend': 0.6, 'atmosphere-blend': 0 };
    style.light = { anchor: 'viewport', color: '#fff5e6', intensity: 0.42, position: [1.3, 210, 35] };
    return style;
}

function addLayers(map) {
    // 3D buildings sit under the labels; the route, cells and halo draw on top of everything.
    const labels = map.getStyle().layers.find((l) => l.type === 'symbol' && l.layout?.visibility !== 'none')?.id;
    // Most Addis footprints in OpenStreetMap carry no height: assume about two storeys.
    const height = ['max', ['coalesce', ['get', 'render_height'], 0], 8];
    map.addLayer({
        id: 'koba-buildings-3d', type: 'fill-extrusion', source: 'openmaptiles', 'source-layer': 'building', minzoom: 12.5,
        paint: {
            'fill-extrusion-color': ['interpolate', ['linear'], height, 8, '#145a43', 20, '#1a6a50', 50, '#247d5f', 120, '#36966f'],
            'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 12.5, 0, 14.2, height],
            'fill-extrusion-base': ['interpolate', ['linear'], ['zoom'], 12.5, 0, 14.2, ['coalesce', ['get', 'render_min_height'], 0]],
            'fill-extrusion-opacity': 0.95,
            'fill-extrusion-vertical-gradient': true,
        },
    }, labels);

    map.addSource('koba-cells', { type: 'geojson', data: empty() });
    map.addLayer({ id: 'koba-cells-fill', type: 'fill', source: 'koba-cells', paint: { 'fill-color': C.sand, 'fill-opacity': ['case', ['get', 'active'], 0.16, 0.03] } });
    map.addLayer({ id: 'koba-cells-line', type: 'line', source: 'koba-cells', paint: { 'line-color': C.sand, 'line-width': ['case', ['get', 'active'], 2, 1], 'line-opacity': ['case', ['get', 'active'], 0.9, 0.24] } });

    map.addSource('koba-accuracy', { type: 'geojson', data: empty() });
    map.addLayer({ id: 'koba-accuracy-fill', type: 'fill', source: 'koba-accuracy', paint: { 'fill-color': C.you, 'fill-opacity': 0.13 } });
    map.addLayer({ id: 'koba-accuracy-line', type: 'line', source: 'koba-accuracy', paint: { 'line-color': C.you, 'line-opacity': 0.5, 'line-width': 1.2 } });

    const round = { 'line-cap': 'round', 'line-join': 'round' };
    map.addSource('koba-route', { type: 'geojson', data: empty(), lineMetrics: true });
    map.addLayer({ id: 'koba-route-glow', type: 'line', source: 'koba-route', layout: round, paint: { 'line-color': C.sand, 'line-width': 18, 'line-blur': 12, 'line-opacity': 0.3 } });
    map.addLayer({ id: 'koba-route-casing', type: 'line', source: 'koba-route', layout: round, paint: { 'line-color': C.casing, 'line-width': 9 } });
    map.addLayer({ id: 'koba-route-line', type: 'line', source: 'koba-route', layout: round, paint: { 'line-width': 5, 'line-gradient': ['interpolate', ['linear'], ['line-progress'], 0, '#7fb3ff', 0.5, '#c9bda0', 1, C.sand] } });

    // No road route (routing down): a dashed straight line instead.
    map.addSource('koba-guess', { type: 'geojson', data: empty() });
    map.addLayer({ id: 'koba-guess-line', type: 'line', source: 'koba-guess', layout: { 'line-cap': 'round' }, paint: { 'line-color': C.sand, 'line-width': 3, 'line-dasharray': [0.6, 1.8], 'line-opacity': 0.85 } });
}

function pinElement(branch, onPick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'koba-pin';
    el.setAttribute('aria-label', branch.name);
    el.innerHTML = '<span class="koba-pin__tag"><span class="koba-pin__name"></span><span class="koba-pin__eta"></span></span>'
        + '<span class="koba-pin__marker" aria-hidden="true"><svg viewBox="0 0 40 50"><path d="M20 49C20 49 2 30.5 2 19a18 18 0 0 1 36 0c0 11.5-18 30-18 30z"/></svg><b>K</b></span>'
        + '<span class="koba-pin__shadow" aria-hidden="true"></span>';
    el.querySelector('.koba-pin__name').textContent = branch.short;
    el.addEventListener('click', (event) => {
        event.stopPropagation();
        onPick(branch.key);
    });
    return el;
}

function meElement() {
    const el = document.createElement('div');
    el.className = 'koba-me';
    el.innerHTML = '<span class="koba-me__halo"></span><span class="koba-me__dot"></span>';
    return el;
}

export async function createLocatorMap(container, { style, branches, reduced, padding, onPick }) {
    const response = await fetch(style);
    if (!response.ok) throw new Error(`Map style ${response.status}`);

    const spots = Object.fromEntries(branches.map((b) => [b.key, [b.coords[1], b.coords[0]]]));
    const all = Object.values(spots).reduce((bounds, p) => bounds.extend(p), new LngLatBounds(Object.values(spots)[0], Object.values(spots)[0]));

    const map = new MapLibre({
        container,
        style: brand(await response.json()),
        bounds: all,
        fitBoundsOptions: { padding: padding(), maxZoom: 13.6 },
        attributionControl: false,
        maxPitch: 72,
        canvasContextAttributes: { antialias: true },
    });

    await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Map timed out')), 20000);
        map.once('load', () => {
            clearTimeout(timer);
            resolve();
        });
    });
    addLayers(map);

    const markers = Object.fromEntries(branches.map((b) => {
        const el = pinElement(b, onPick);
        return [b.key, { el, marker: new Marker({ element: el, anchor: 'bottom' }).setLngLat(spots[b.key]).addTo(map) }];
    }));
    const me = new Marker({ element: meElement(), anchor: 'center' });

    let you = null;      // [lng, lat] once the visitor is in town
    let flown = false;
    let raf = 0;
    let selection = 0;
    let current = { key: null, route: null, landed: false };
    const duration = (ms) => (reduced ? 0 : ms);

    /** Shift so a point lands in the middle of the part of the map the panel doesn't cover. */
    const offset = () => {
        const p = padding();
        return [(p.left - p.right) / 2, (p.top - p.bottom) / 2];
    };

    const setLine = (id, coordinates) => map.getSource(id)?.setData(coordinates ? line(coordinates) : empty());

    const mark = (key) => Object.entries(markers).forEach(([k, m]) => m.el.classList.toggle('is-selected', k === key));

    /** Frame the visitor and the café; in 3D, turn the map so the café is ahead of them. */
    const frame = (key, { threeD = true, ms = 2400 } = {}) => {
        const target = spots[key];
        if (!target) return;
        if (!you) {
            map.flyTo({ center: target, zoom: 15.4, pitch: threeD ? 55 : 0, bearing: 0, offset: offset(), duration: duration(1800), essential: true });
            return;
        }
        const heading = threeD ? bearing(you, target) : 0;
        const camera = map.cameraForBounds(new LngLatBounds(you, you).extend(target), { padding: padding(), bearing: heading, maxZoom: 16.4 });
        if (!camera) return;
        map.flyTo({ center: camera.center, zoom: Math.min(16.4, camera.zoom - (threeD ? 0.5 : 0.1)), bearing: heading, pitch: threeD ? 58 : 0, duration: duration(ms), curve: 1.42, essential: true });
    };

    /** Draw the road from the visitor's dot to the café's door. */
    const drawRoute = (route, key) => {
        cancelAnimationFrame(raf);
        if (map.getSource('koba-route') === undefined) return;
        const target = spots[key];
        if (!route || !you) {
            setLine('koba-route', null);
            setLine('koba-guess', you ? [you, target] : null);
            return;
        }
        setLine('koba-guess', null);
        const path = [you, ...route.coordinates, target];
        if (reduced) {
            setLine('koba-route', path);
            return;
        }
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / 1500);
            setLine('koba-route', path.slice(0, Math.max(2, Math.ceil((1 - (1 - t) ** 3) * path.length))));
            if (t < 1) raf = requestAnimationFrame(step);
        };
        raf = requestAnimationFrame(step);
    };

    const api = {
        showFix(fix) {
            const point = [fix.lng, fix.lat];
            if (Math.min(...Object.values(spots).map((p) => km(point, p))) > FOLLOW_KM) {
                you = null;
                me.remove();
                return;
            }
            you = point;
            me.setLngLat(point).addTo(map);
            map.getSource('koba-accuracy').setData(circle(point, Math.min(fix.accuracy, 5000)));
            if (!flown) {
                flown = true;
                map.flyTo({ center: point, zoom: fix.accuracy > 1500 ? 12.6 : 14.6, pitch: 0, bearing: 0, offset: offset(), duration: duration(1600), essential: true });
            }
        },

        showResult(result, key, { threeD = true } = {}) {
            result.branches.forEach((b) => {
                const m = markers[b.key];
                if (!m) return;
                m.el.querySelector('.koba-pin__eta').textContent = result.in_area ? `${Math.max(1, Math.round(b.drive_s / 60))} min` : '';
                m.el.classList.toggle('is-best', b.key === result.recommended);
                m.el.classList.toggle('is-paused', !b.takes_orders);
            });

            const { cell, ring } = result.origin;
            map.getSource('koba-cells').setData({
                type: 'FeatureCollection',
                features: [
                    ...ring.map((polygon) => ({ type: 'Feature', properties: { active: false }, geometry: { type: 'Polygon', coordinates: [polygon] } })),
                    ...(cell ? [{ type: 'Feature', properties: { active: true }, geometry: { type: 'Polygon', coordinates: [cell.polygon] } }] : []),
                ],
            });

            if (!result.in_area) {
                mark(null);
                map.fitBounds(all, { padding: padding(), pitch: 0, bearing: 0, maxZoom: 13.6, duration: duration(1400) });
                return;
            }
            api.setThreeD(threeD, null);
            api.showSelection(key, null, { threeD });
        },

        showSelection(key, route, { threeD = true } = {}) {
            const token = ++selection;
            current = { key, route, landed: false };
            mark(key);
            setLine('koba-route', null);
            setLine('koba-guess', null);
            frame(key, { threeD });
            // Draw once the camera arrives (or soon anyway); a newer selection cancels this one.
            const land = () => {
                if (token !== selection || current.landed) return;
                current.landed = true;
                drawRoute(current.route, key);
            };
            if (reduced) return land();
            map.once('moveend', land);
            setTimeout(land, 2800);
        },

        /** The road line arrived: draw it now if the camera is already there, else on landing. */
        setRoute(key, route) {
            if (current.key !== key) return;
            current.route = route;
            if (current.landed) drawRoute(route, key);
        },

        focusBranch(key) {
            mark(key);
            frame(key, { threeD: map.getPitch() > 10, ms: 1600 });
        },

        frame: (key, { threeD = true } = {}) => frame(key, { threeD, ms: 1400 }),

        setThreeD(on, key) {
            map.setLayoutProperty('koba-buildings-3d', 'visibility', on ? 'visible' : 'none');
            map.setLayoutProperty('building', 'visibility', on ? 'none' : 'visible');
            if (key) frame(key, { threeD: on, ms: 1300 });
        },

        destroy() {
            cancelAnimationFrame(raf);
            map.remove();
        },
    };

    return api;
}

import { isDark } from '../dashboard/theme';

/*
 * KOBA chart palette, derived from the brand greens, sand and earth brown.
 * Categorical slots validated (lightness band, chroma, colour-blind and
 * normal-vision separation) against the light card surface and the dark
 * #0c241c surface. Slots are assigned in this fixed order, never cycled,
 * and an entity (e.g. a branch) keeps its slot when others are filtered out.
 */
const light = {
    series: ['#0f7a52', '#d39a2c', '#3d79b0', '#9c5a1a', '#8b5a9e', '#5aa86f'],
    previous: 'rgba(6, 46, 34, 0.36)',
    grid: 'rgba(6, 46, 34, 0.08)',
    text: 'rgba(6, 46, 34, 0.6)',
    ink: '#062e22',
    surface: '#fffdf9',
    target: '#b42318',
    // Sequential (heatmap): one hue, cream to deep green.
    heat: ['#f3efe6', '#d7e7dc', '#a9cfb8', '#6eac8b', '#2f8460', '#0b4a36'],
    status: { pending: '#d39a2c', preparing: '#3d79b0', ready: '#0f7a52', completed: '#a3aea7', cancelled: '#b42318' },
};

const dark = {
    series: ['#1a9078', '#bd8428', '#5a92cc', '#bb6f2c', '#a479c0', '#6aa84e'],
    previous: 'rgba(246, 239, 227, 0.4)',
    grid: 'rgba(246, 239, 227, 0.07)',
    text: 'rgba(246, 239, 227, 0.6)',
    ink: '#f6efe3',
    surface: '#0c241c',
    target: '#f07a6a',
    heat: ['#10291f', '#15402f', '#1b5a42', '#237757', '#2e966d', '#46b98b'],
    status: { pending: '#bd8428', preparing: '#5a92cc', ready: '#1a9078', completed: '#56665f', cancelled: '#e5604f' },
};

export const palette = () => (isDark() ? dark : light);

/** Colour for an entity by its fixed position in the full list (e.g. all branch keys). */
export function entityColor(key, order, fallbackIndex = 0) {
    const series = palette().series;
    const index = order?.indexOf(key);
    return series[(index >= 0 ? index : fallbackIndex) % series.length];
}

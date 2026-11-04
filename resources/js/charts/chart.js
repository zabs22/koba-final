import { buildOptions, isEmpty } from './options';

// ApexCharts is the largest dependency: loaded as its own chunk on pages that draw a chart.
let apex = null;
const loadApex = () => (apex ??= import('apexcharts').then((module) => module.default));

const clone = (value) => (value === undefined ? undefined : JSON.parse(JSON.stringify(value)));
const path = (object, keys) => keys.split('.').reduce((value, key) => value?.[key], object);

/*
 * <x-dashboard.chart>: renders an ApexCharts chart from either
 *  - `source`: a dot path into the enclosing page's `payload` (updates live
 *    whenever the page refreshes its data), or
 *  - `data`: a fixed JSON object.
 * Re-renders on theme change; shows an empty state when there is no data.
 * Clicking a point navigates to `href` ("{key}" replaced) or dispatches
 * `chart-select` with the clicked entity's key.
 */
export default (config) => ({
    empty: false,
    ready: false,
    chart: null,

    init() {
        this.draw();
        if (config.source) this.$watch('payload', () => this.update());
        this._onTheme = () => this.draw();
        window.addEventListener('theme-changed', this._onTheme);
    },

    destroy() {
        window.removeEventListener('theme-changed', this._onTheme);
        this.chart?.destroy();
    },

    data() {
        return clone(config.source ? path(this.payload, config.source) : config.data);
    },

    options(data) {
        const options = buildOptions(config.type, data, { height: config.height, format: config.format, colors: config.colors, target: config.target ?? data?.target, entities: config.entities, tone: config.tone });
        options.chart.events = { dataPointSelection: (event, ctx, { seriesIndex, dataPointIndex }) => this.select(data, seriesIndex, dataPointIndex) };
        return options;
    },

    draw() {
        const data = this.data();
        this.chart?.destroy();
        this.chart = null;
        this.empty = isEmpty(config.type, data);
        if (this.empty) { this.ready = true; return; }

        loadApex().then((ApexCharts) => {
            this.chart?.destroy();
            this.chart = new ApexCharts(this.$refs.canvas, this.options(data));
            this.chart.render().then(() => { this.ready = true; });
        });
    },

    update() {
        const data = this.data();
        const empty = isEmpty(config.type, data);
        if (!this.chart || empty !== this.empty) { this.draw(); return; }
        this.chart.updateOptions(this.options(data), false, true);
    },

    select(data, seriesIndex, pointIndex) {
        const key = data?.keys?.[pointIndex] ?? data?.series?.[seriesIndex]?.key ?? data?.series?.[seriesIndex]?.data?.[pointIndex]?.order;
        if (!key) return;
        if (config.href) { window.location.href = config.href.replace('{key}', encodeURIComponent(key)); return; }
        this.$dispatch('chart-select', { chart: config.source, key });
    },
});

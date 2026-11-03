import { entityColor, palette } from './palette';
import { format } from '../dashboard/format';
import { isDark } from '../dashboard/theme';

/*
 * ApexCharts option builders, one per chart form used on the dashboards.
 * Shared rules: thin 2px lines, 4px rounded bar ends, a 2px surface gap
 * between fills, recessive grid, no data labels on every point, tooltips in
 * ETB / units, and the previous period always a neutral dashed line.
 */
function base(height, valueFormat) {
    const p = palette();

    return {
        chart: {
            height,
            fontFamily: 'Figtree, ui-sans-serif, system-ui, sans-serif',
            foreColor: p.text,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 380, animateGradually: { enabled: false }, dynamicAnimation: { enabled: true, speed: 380 } },
            parentHeightOffset: 0,
        },
        theme: { mode: isDark() ? 'dark' : 'light' },
        grid: { borderColor: p.grid, strokeDashArray: 3, padding: { left: 4, right: 8, top: 0 }, xaxis: { lines: { show: false } } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', markers: { size: 5, offsetX: -2 }, itemMargin: { horizontal: 8 }, labels: { colors: p.text } },
        tooltip: { theme: isDark() ? 'dark' : 'light', y: { formatter: (v) => format(v, valueFormat) } },
        states: { hover: { filter: { type: 'darken', value: 0.92 } }, active: { filter: { type: 'none' } } },
        xaxis: { axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '11px' }, hideOverlappingLabels: true, rotate: 0 }, tooltip: { enabled: false } },
        yaxis: { labels: { style: { fontSize: '11px' }, formatter: (v) => format(v, valueFormat, { abbreviate: true }) } },
    };
}

const isPrevious = (s) => /previous/i.test(s.name);

const builders = {
    /** This period (filled) against the previous period (dashed, neutral). */
    area(data, { height, format: fmt }) {
        const p = palette();
        const series = data.series || [];
        return {
            ...base(height, fmt),
            chart: { ...base(height, fmt).chart, type: 'area' },
            series,
            colors: series.map((s, i) => (isPrevious(s) ? p.previous : p.series[i])),
            stroke: { curve: 'smooth', width: series.map((s) => (isPrevious(s) ? 1.5 : 2)), dashArray: series.map((s) => (isPrevious(s) ? 5 : 0)) },
            fill: { type: 'gradient', gradient: { shadeIntensity: 0, opacityFrom: series.map((s) => (isPrevious(s) ? 0 : 0.22)), opacityTo: 0, stops: [0, 95] } },
            markers: { size: 0, strokeWidth: 2, strokeColors: p.surface, hover: { size: 5 } },
            xaxis: { ...base(height, fmt).xaxis, categories: data.categories, tickAmount: Math.min(8, (data.categories || []).length) },
            tooltip: { ...base(height, fmt).tooltip, shared: true, intersect: false },
        };
    },

    line(data, { height, format: fmt, target }) {
        const p = palette();
        const opts = builders.area(data, { height, format: fmt });
        return {
            ...opts,
            chart: { ...opts.chart, type: 'line' },
            fill: { type: 'solid', opacity: 1 },
            markers: { size: 3, strokeWidth: 2, strokeColors: p.surface, hover: { size: 5 } },
            annotations: target ? { yaxis: [{ y: target, borderColor: p.target, strokeDashArray: 4, label: { text: `Target ${target} min`, position: 'left', textAnchor: 'start', offsetX: 6, borderWidth: 0, style: { background: 'transparent', color: p.target, fontSize: '11px', fontWeight: 600 } } }] } : {},
        };
    },

    /** Grouped bars; "Previous period" in neutral. */
    bar(data, { height, format: fmt, stacked = false, entities }) {
        const p = palette();
        const series = data.series || [];
        return {
            ...base(height, fmt),
            chart: { ...base(height, fmt).chart, type: 'bar', stacked },
            series,
            colors: series.map((s, i) => (isPrevious(s) ? p.previous : (s.key ? entityColor(s.key, entities, i) : p.series[i]))),
            plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', borderRadiusWhenStacked: 'last', columnWidth: stacked ? '58%' : '68%' } },
            stroke: { show: true, width: 2, colors: [p.surface] },
            xaxis: { ...base(height, fmt).xaxis, categories: data.categories, tickAmount: (data.categories || []).length > 12 ? 8 : undefined },
            tooltip: { ...base(height, fmt).tooltip, shared: true, intersect: false },
        };
    },

    stacked(data, opts) {
        return builders.bar(data, { ...opts, stacked: true });
    },

    /** Horizontal ranking: one hue (magnitude), longest first. */
    hbar(data, { height, format: fmt }) {
        const p = palette();
        return {
            ...base(height, fmt),
            chart: { ...base(height, fmt).chart, type: 'bar' },
            series: data.series || [],
            colors: [p.series[0]],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '62%' } },
            grid: { ...base(height, fmt).grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            xaxis: { ...base(height, fmt).xaxis, categories: data.categories, labels: { formatter: (v) => format(v, fmt, { abbreviate: true }), style: { fontSize: '11px' } } },
            yaxis: { labels: { style: { fontSize: '12px', colors: p.ink }, maxWidth: 150 } },
            legend: { show: false },
        };
    },

    donut(data, { height, format: fmt, colors: colorMode }) {
        const p = palette();
        const keys = data.keys || [];
        const colors = colorMode === 'status' ? keys.map((k) => p.status[k]) : (data.labels || []).map((_, i) => p.series[i % p.series.length]);
        const total = (w) => format(w.globals.seriesTotals.reduce((a, b) => a + b, 0), fmt, { abbreviate: true });
        return {
            ...base(height, fmt),
            chart: { ...base(height, fmt).chart, type: 'donut' },
            series: data.series || [],
            labels: data.labels || [],
            colors,
            stroke: { width: 2, colors: [p.surface] },
            legend: { ...base(height, fmt).legend, position: 'bottom', horizontalAlign: 'center' },
            plotOptions: { pie: { expandOnClick: false, donut: { size: '72%', labels: { show: true, name: { fontSize: '12px', color: p.text, offsetY: 18 }, value: { fontSize: '20px', fontWeight: 600, color: p.ink, offsetY: -14, formatter: (v) => format(Number(v), fmt, { abbreviate: true }) }, total: { show: true, label: 'Total', color: p.text, fontSize: '12px', formatter: total } } } } },
            tooltip: { ...base(height, fmt).tooltip, fillSeriesColor: false },
        };
    },

    heatmap(data, { height }) {
        const p = palette();
        const max = Math.max(1, ...(data.series || []).flatMap((s) => s.data.map((d) => d.y)));
        const steps = p.heat.length - 1;
        return {
            ...base(height, 'number'),
            chart: { ...base(height, 'number').chart, type: 'heatmap' },
            series: data.series || [],
            stroke: { width: 2, colors: [p.surface] },
            plotOptions: { heatmap: { radius: 3, enableShades: false, colorScale: { ranges: p.heat.map((color, i) => ({ from: i === 0 ? 0 : Math.floor((max * (i - 1)) / steps) + 1, to: i === 0 ? 0 : Math.ceil((max * i) / steps), color, name: i === 0 ? 'None' : undefined })) } } },
            legend: { show: false },
            xaxis: { ...base(height, 'number').xaxis, labels: { style: { fontSize: '10px' } } },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            tooltip: { ...base(height, 'number').tooltip, y: { formatter: (v) => `${v} orders` } },
        };
    },

    /** Pickup timeline: one bar per order from kitchen start to pickup, coloured by status. */
    timeline(data, { height }) {
        const p = palette();
        const series = (data.series || []).map((s) => ({ ...s, data: s.data.map((d) => ({ ...d, fillColor: p.status[d.status] })) }));
        return {
            ...base(height, 'number'),
            chart: { ...base(height, 'number').chart, type: 'rangeBar' },
            series,
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '58%', rangeBarGroupRows: false } },
            xaxis: { type: 'datetime', labels: { datetimeUTC: false, format: 'HH:mm', style: { fontSize: '11px' } }, axisBorder: { show: false } },
            yaxis: { labels: { style: { fontSize: '11px', colors: p.ink }, maxWidth: 120 } },
            grid: { ...base(height, 'number').grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            annotations: data.now ? { xaxis: [{ x: data.now, borderColor: p.target, strokeDashArray: 0, label: { text: 'Now', borderWidth: 0, orientation: 'horizontal', style: { background: p.target, color: '#fff', fontSize: '10px' } } }] } : {},
            legend: { show: false },
            tooltip: {
                theme: isDark() ? 'dark' : 'light',
                custom: ({ seriesIndex, dataPointIndex, w }) => {
                    const d = w.config.series[seriesIndex].data[dataPointIndex];
                    const t = (ms) => new Date(ms).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Addis_Ababa' });
                    return `<div class="px-3 py-2 text-xs"><div class="font-semibold">${d.order}</div><div>${d.x}</div><div class="opacity-70">Kitchen ${t(d.y[0])} · pickup ${t(d.y[1])}</div></div>`;
                },
            },
        };
    },

    /** KPI sparkline: no axes, no grid, tooltip only. */
    spark(data, { height, format: fmt, tone }) {
        const p = palette();
        const color = tone === 'down' ? p.target : p.series[0];
        return {
            chart: { type: 'area', height, sparkline: { enabled: true }, animations: { enabled: true, speed: 380 }, background: 'transparent' },
            series: [{ name: 'Value', data: Array.isArray(data) ? data : [] }],
            colors: [color],
            stroke: { curve: 'smooth', width: 1.75 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 0, opacityFrom: 0.24, opacityTo: 0, stops: [0, 100] } },
            tooltip: { theme: isDark() ? 'dark' : 'light', fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: () => '' }, formatter: (v) => format(v, fmt) }, marker: { show: false } },
        };
    },
};

export function buildOptions(type, data, opts = {}) {
    const build = builders[type];
    if (!build) throw new Error(`Unknown chart type "${type}"`);
    return build(data, opts);
}

/** True when there is nothing to draw. */
export function isEmpty(type, data) {
    if (type === 'spark') return !Array.isArray(data) || data.length === 0;
    if (type === 'donut') return !(data?.series || []).some((v) => v > 0);
    return !(data?.series || []).some((s) => (s.data || []).some((d) => (typeof d === 'object' && d !== null ? (Array.isArray(d.y) ? true : d.y > 0) : d > 0)));
}

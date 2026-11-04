/*
 * Number and time formatting shared by KPI cards, tables, charts and tooltips.
 * Money is always shown as "ETB 12,450.00".
 */
const money = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const plain = new Intl.NumberFormat('en-US', { maximumFractionDigits: 1 });
const compact = new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 });

export const etb = (value) => `ETB ${money.format(Number(value) || 0)}`;
export const etbShort = (value) => `ETB ${Math.abs(value) >= 10000 ? compact.format(value) : plain.format(Math.round(value || 0))}`;
export const number = (value) => plain.format(Number(value) || 0);
export const short = (value) => (Math.abs(value) >= 10000 ? compact.format(value) : plain.format(value || 0));

/** Format by the KPI/chart "format" key: etb | number | percent | minutes. */
export function format(value, kind = 'number', { abbreviate = false } = {}) {
    if (value === null || value === undefined || Number.isNaN(value)) return '—';
    switch (kind) {
        case 'etb': return abbreviate ? etbShort(value) : etb(value);
        case 'percent': return `${plain.format(value)}%`;
        case 'minutes': return `${plain.format(value)} min`;
        default: return abbreviate ? short(value) : number(value);
    }
}

export const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** "4 min", "1 h 12 min" from a number of seconds. */
export function duration(seconds) {
    const s = Math.max(0, Math.round(Math.abs(seconds)));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    if (h) return `${h} h ${m} min`;
    if (m) return `${m} min`;
    return `${s % 60}s`;
}

/** "12:04" elapsed-timer style. */
export function stopwatch(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    const h = Math.floor(s / 3600);
    const mm = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const ss = String(s % 60).padStart(2, '0');
    return h ? `${h}:${mm}:${ss}` : `${mm}:${ss}`;
}

export const clock = (unix) => new Date(unix * 1000).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Addis_Ababa' });

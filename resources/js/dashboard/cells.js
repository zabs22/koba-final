import { escape, format } from './format';
import { icon } from './icons';

/*
 * HTML for data-table cells and badges, by column type. Everything that comes
 * from data is escaped here.
 */
const STATUS = {
    pending: ['New', 'bg-sand-100 text-brown-700 dark:bg-sand-400/15 dark:text-sand-300'],
    preparing: ['Preparing', 'bg-info-soft text-info'],
    ready: ['Ready', 'bg-success-soft text-success'],
    completed: ['Picked up', 'bg-subtle text-ink-soft ring-1 ring-inset ring-line'],
    cancelled: ['Cancelled', 'bg-danger-soft text-danger'],
    open: ['Open', 'bg-success-soft text-success'],
    paused: ['Paused', 'bg-warning-soft text-warning'],
    closed: ['Closed', 'bg-subtle text-ink-soft ring-1 ring-inset ring-line'],
    paid: ['Paid', 'bg-success-soft text-success'],
    due: ['Due at pickup', 'bg-warning-soft text-warning'],
    refunded: ['Refunded', 'bg-info-soft text-info'],
    void: ['Void', 'bg-subtle text-ink-faint ring-1 ring-inset ring-line'],
    low: ['Low stock', 'bg-warning-soft text-warning'],
    out: ['Sold out', 'bg-danger-soft text-danger'],
    ok: ['In stock', 'bg-success-soft text-success'],
    VIP: ['VIP', 'bg-forest-900 text-sand-300 dark:bg-sand-400/20 dark:text-sand-200'],
    Regular: ['Regular', 'bg-success-soft text-success'],
    New: ['New', 'bg-sand-100 text-brown-700 dark:bg-sand-400/15 dark:text-sand-300'],
    Occasional: ['Occasional', 'bg-subtle text-ink-soft ring-1 ring-inset ring-line'],
    Inactive: ['Inactive', 'bg-subtle text-ink-faint ring-1 ring-inset ring-line'],
    active: ['Active', 'bg-success-soft text-success'],
    inactive: ['Deactivated', 'bg-subtle text-ink-faint ring-1 ring-inset ring-line'],
};

const DOT = { pending: 'bg-sand-500', preparing: 'bg-info', ready: 'bg-success', open: 'bg-success', paused: 'bg-warning', closed: 'bg-ink-faint', cancelled: 'bg-danger' };

export function badge(value, label = null) {
    const [text, cls] = STATUS[value] ?? [value, 'bg-subtle text-ink-soft ring-1 ring-inset ring-line'];
    const dot = DOT[value] ? `<span class="size-1.5 rounded-full ${DOT[value]}" aria-hidden="true"></span>` : '';
    return `<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ${cls}">${dot}${escape(label ?? text)}</span>`;
}

export function avatar(initials, tone = 0) {
    const tones = ['bg-forest-100 text-forest-800 dark:bg-forest-400/20 dark:text-forest-100', 'bg-sand-100 text-brown-700 dark:bg-sand-400/15 dark:text-sand-200', 'bg-subtle text-ink-soft'];
    return `<span class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-semibold ${tones[tone % tones.length]}" aria-hidden="true">${escape(initials)}</span>`;
}

export function thumb(src, name, iconName = 'image') {
    return src
        ? `<img src="${escape(src)}" alt="" loading="lazy" class="size-10 shrink-0 rounded-lg object-cover ring-1 ring-line">`
        : `<span class="grid size-10 shrink-0 place-items-center rounded-lg bg-forest-900 text-sand-300" aria-hidden="true">${icon(iconName, 'size-4')}</span>`;
}

export function cell(column, row) {
    const value = row[column.key];
    switch (column.type) {
        case 'money': return `<span class="tabular-nums">${format(value, 'etb')}</span>`;
        case 'number': return `<span class="tabular-nums">${format(value, 'number')}</span>`;
        case 'minutes': return value ? `<span class="tabular-nums">${format(value, 'minutes')}</span>` : '<span class="text-ink-faint">—</span>';
        case 'percent': return `<span class="tabular-nums">${format(value, 'percent')}</span>`;
        case 'badge': return badge(value, column.labelKey ? row[column.labelKey] : null);
        case 'mono': return `<span class="font-mono text-[12.5px] tracking-tight">${escape(value)}</span>`;
        case 'rating': return value ? `<span class="inline-flex items-center gap-1 tabular-nums">${icon('star', 'size-3.5 fill-sand-400 text-sand-500')}${Number(value).toFixed(1)}</span>` : '<span class="text-ink-faint">—</span>';
        case 'person': return `<span class="flex items-center gap-3 min-w-0">${avatar(row.initials, (row.customer_id ?? row.id ?? 0) % 3)}<span class="min-w-0"><span class="block truncate font-medium text-ink">${escape(value)}</span>${column.sub ? `<span class="block truncate text-xs text-ink-faint">${escape(row[column.sub])}</span>` : ''}</span></span>`;
        case 'product': return `<span class="flex items-center gap-3 min-w-0">${thumb(row.image, value, 'croissant')}<span class="min-w-0"><span class="block truncate font-medium text-ink">${escape(value)}</span><span class="block truncate text-xs text-ink-faint">${escape(row[column.sub ?? 'category_name'])}</span></span></span>`;
        case 'stack': return `<span class="block min-w-0"><span class="block truncate text-ink">${escape(value)}</span><span class="block truncate text-xs text-ink-faint">${escape(row[column.sub])}</span></span>`;
        case 'muted': return `<span class="text-ink-soft">${escape(value)}</span>`;
        case 'truncate': return `<span class="block max-w-[22ch] truncate text-ink-soft" title="${escape(value)}">${escape(value)}</span>`;
        case 'change': return value === null || value === undefined ? '<span class="text-ink-faint">—</span>' : `<span class="tabular-nums font-medium ${value >= 0 ? 'text-success' : 'text-danger'}">${value > 0 ? '+' : ''}${Number(value).toFixed(1)}%</span>`;
        case 'bar': return `<span class="flex items-center gap-2"><span class="h-1.5 w-20 overflow-hidden rounded-full bg-subtle ring-1 ring-inset ring-line"><span class="block h-full rounded-full bg-forest-500" style="width:${Math.min(100, Number(value) || 0)}%"></span></span><span class="tabular-nums text-xs text-ink-soft">${format(value, 'percent')}</span></span>`;
        default: return escape(value);
    }
}

@props([
    'columns',                 // [{key, label, type?, sortable?, align?, sub?, mobile?: false}]
    'rows' => [],              // local mode
    'endpoint' => null,        // remote mode (JSON from App\Support\Dashboard\TableQuery)
    'filters' => [],           // [key => ['label' => …, 'options' => [value => label]]]
    'searchable' => [],
    'search' => 'Search…',
    'perPage' => 12,
    'sort' => null,
    'dir' => 'desc',
    'selectable' => false,
    'rowEvent' => null,        // event dispatched with the row on click (e.g. open-order)
    'exportName' => null,      // shows "Export CSV" (selected rows, or the current page)
    'poll' => null,            // remote mode: refresh every N seconds
    'sum' => false,            // footer total of the "total" column
    'emptyTitle' => 'Nothing here yet',
    'emptyBody' => 'Try another search or filter.',
])

@php
    $config = [
        'columns' => $columns,
        'rows' => $rows,
        'endpoint' => $endpoint,
        'perPage' => $perPage,
        'sort' => $sort,
        'dir' => $dir,
        'filters' => collect($filters)->map(fn ($f) => $f['value'] ?? '')->all(),
        'searchable' => $searchable,
        'selectable' => $selectable,
        'rowEvent' => $rowEvent,
    ];
@endphp

<div x-data="dataTable(@js($config))" @if ($poll) data-poll="{{ $poll }}" @endif {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
        <label class="relative min-w-[200px] flex-1 sm:max-w-xs">
            <span class="sr-only">Search</span>
            <x-dashboard.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-faint" />
            <input type="search" x-model="q" placeholder="{{ $search }}" class="field h-9 pl-9 text-[13px]">
        </label>

        @foreach ($filters as $key => $filter)
            <label class="sr-only" for="filter-{{ $key }}">{{ $filter['label'] }}</label>
            <select id="filter-{{ $key }}" x-model="filters.{{ $key }}" class="field h-9 w-auto py-0 pr-8 text-[13px]">
                <option value="">{{ $filter['label'] }}: all</option>
                @foreach ($filter['options'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        @endforeach

        {{ $toolbar ?? '' }}

        <div class="ml-auto flex items-center gap-2">
            @if ($selectable)
                <span x-show="selected.length" x-cloak class="text-[13px] text-ink-soft"><span class="font-semibold tabular-nums" x-text="selected.length"></span> selected</span>
                <button type="button" x-show="selected.length" x-cloak class="btn-ghost btn-sm" @click="selected = []">Clear</button>
            @endif
            {{ $bulk ?? '' }}
            @if ($exportName)
                <button type="button" class="btn-secondary btn-sm" @click="exportCsv('{{ $exportName }}')">
                    <x-dashboard.icon name="download" class="size-3.5" /><span x-text="selected.length ? 'Export selected' : 'Export CSV'"></span>
                </button>
            @endif
        </div>
    </div>

    {{-- Optional grid view (enclosing component sets `view` to "grid") --}}
    @isset($grid)
        <div x-show="$data.view === 'grid'" :class="loading && 'opacity-50'">{{ $grid }}</div>
    @endisset

    {{-- Desktop / tablet: table --}}
    <div class="relative hidden max-h-[680px] overflow-auto md:block" x-show="$data.view !== 'grid'">
        <table class="table">
            <thead>
                <tr>
                    @if ($selectable)
                        <th class="w-10"><input type="checkbox" class="rounded border-line-strong text-accent focus:ring-accent/30" :checked="allSelected" @change="toggleAll()" aria-label="Select all on this page"></th>
                    @endif
                    <template x-for="col in columns" :key="col.key">
                        <th scope="col" :class="col.align === 'right' && 'text-right'" :aria-sort="sort === col.key ? (dir === 'asc' ? 'ascending' : 'descending') : null">
                            <template x-if="col.sortable">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-ink" :class="sort === col.key && 'text-ink'" @click="sortBy(col)">
                                    <span x-text="col.label"></span>
                                    <span x-html="$icon(sort === col.key ? (dir === 'asc' ? 'arrow-up' : 'arrow-down') : 'chevrons-up-down', 'size-3')"></span>
                                </button>
                            </template>
                            <template x-if="!col.sortable"><span x-text="col.label"></span></template>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody :class="loading && 'opacity-50'">
                @isset($row)
                    <template x-for="row in rows" :key="rowId(row)">{{ $row }}</template>
                @else
                    <template x-for="row in rows" :key="rowId(row)">
                        <tr @click="open(row)" :class="'{{ $rowEvent }}' && 'cursor-pointer'">
                            @if ($selectable)
                                <td class="w-10" @click.stop><input type="checkbox" class="rounded border-line-strong text-accent focus:ring-accent/30" :value="rowId(row)" x-model="selected" :aria-label="'Select ' + rowId(row)"></td>
                            @endif
                            <template x-for="col in columns" :key="col.key">
                                <td :class="col.align === 'right' && 'text-right'" x-html="render(col, row)"></td>
                            </template>
                        </tr>
                    </template>
                @endisset
            </tbody>
        </table>
        <template x-if="loading && !rows.length">
            <div class="space-y-3 p-4">@for ($i = 0; $i < 6; $i++)<div class="skeleton h-9"></div>@endfor</div>
        </template>
    </div>

    {{-- Phones: the same rows as cards --}}
    <ul class="divide-y divide-line md:hidden" :class="loading && 'opacity-50'" x-show="$data.view !== 'grid'">
        @isset($card)
            <template x-for="row in rows" :key="rowId(row)">{{ $card }}</template>
        @else
            <template x-for="row in rows" :key="rowId(row)">
                <li class="px-4 py-3.5" @click="open(row)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 text-[13.5px]" x-html="render(columns[0], row)"></div>
                        <div class="shrink-0" x-html="render(columns[columns.length - 1], row)"></div>
                    </div>
                    <dl class="mt-2.5 grid grid-cols-2 gap-x-4 gap-y-1.5 text-[12.5px]">
                        <template x-for="col in columns.slice(1, -1).filter(c => c.mobile !== false)" :key="col.key">
                            <div class="min-w-0"><dt class="text-ink-faint" x-text="col.label"></dt><dd class="truncate" x-html="render(col, row)"></dd></div>
                        </template>
                    </dl>
                </li>
            </template>
        @endisset
    </ul>

    <template x-if="!loading && !rows.length">
        <x-dashboard.empty-state icon="search" :title="$emptyTitle" :body="$emptyBody" />
    </template>

    {{-- Footer: range, total, pagination --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-[13px] text-ink-soft" x-show="total > 0">
        <p>
            <span x-text="range()"></span>
            @if ($sum)<span class="mx-1.5 text-ink-faint">·</span><span class="font-medium text-ink tabular-nums" x-text="$fmt(sum, 'etb')"></span>@endif
        </p>
        <nav class="flex items-center gap-1" aria-label="Pagination" x-show="pages > 1">
            <button type="button" class="btn-icon size-8" :disabled="page <= 1" @click="go(page - 1)" aria-label="Previous page"><x-dashboard.icon name="chevron-left" /></button>
            <span class="px-2 tabular-nums">Page <span x-text="page"></span> of <span x-text="pages"></span></span>
            <button type="button" class="btn-icon size-8" :disabled="page >= pages" @click="go(page + 1)" aria-label="Next page"><x-dashboard.icon name="chevron-right" /></button>
        </nav>
    </div>
</div>

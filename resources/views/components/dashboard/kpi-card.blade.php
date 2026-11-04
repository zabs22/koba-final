@props(['metric', 'format' => 'number', 'live' => false, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

{{-- KPI from the page payload (payload.kpis[metric]): icon, label, value (tweened), change vs previous period, sparkline. --}}
<{{ $tag }} @if ($href) href="{{ $href }}" @endif x-data="kpi('{{ $metric }}')" {{ $attributes->merge(['class' => 'card group relative flex min-w-0 flex-col p-4'.($href ? ' transition-colors hover:border-line-strong' : '')]) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="flex min-w-0 items-center gap-2 text-[13px] text-ink-soft">
            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-accent-soft text-accent" x-html="$icon(metric().icon, 'size-[15px]')"></span>
            <span class="truncate" x-text="metric().label"></span>
        </span>
        @if ($live)
            <span class="inline-flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-ink-faint"><span class="live-dot"></span>Live</span>
        @else
            <template x-if="trend()">
                <span class="inline-flex shrink-0 items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums"
                      :class="trend().good ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger'"
                      :title="payload.range?.compare">
                    <span x-html="$icon(trend().up ? 'arrow-up-right' : 'arrow-down-right', 'size-3')"></span><span x-text="trend().label"></span>
                </span>
            </template>
        @endif
    </div>

    <div class="mt-4 flex items-end justify-between gap-3">
        <p class="kpi-value min-w-0 truncate font-display text-[25px] leading-none font-semibold tracking-tight text-ink" x-text="display()"></p>
        <div class="-mb-1 h-10 w-[92px] shrink-0" x-show="(metric().spark || []).length > 1">
            <x-dashboard.chart type="spark" :source="'kpis.'.$metric.'.spark'" :format="$format" :height="40" />
        </div>
    </div>
    <p class="mt-2 truncate text-[11.5px] text-ink-faint" x-text="{{ $live ? "'Right now'" : "payload.range?.compare ?? ''" }}"></p>
</{{ $tag }}>

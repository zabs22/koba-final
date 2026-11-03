@props([
    'type',                 // area | line | bar | stacked | hbar | donut | heatmap | timeline | spark
    'source' => null,       // dot path into the page payload (live), e.g. "charts.revenue"
    'data' => null,         // or fixed data
    'height' => 280,
    'format' => 'number',   // etb | number | percent | minutes
    'colors' => null,       // donut: "status" to use the order-status colours
    'href' => null,         // click a point → this URL with {key}
    'entities' => null,     // fixed colour order for entities (e.g. all branch keys)
    'target' => null,
    'empty' => 'Nothing to show for this period yet.',
])

@php
    $config = array_filter(compact('type', 'source', 'data', 'height', 'format', 'colors', 'href', 'entities', 'target'), fn ($v) => $v !== null);
@endphp

@if ($type === 'spark')
    <div x-data="chart(@js($config))" {{ $attributes }}><div x-ref="canvas"></div></div>
@else
    <div x-data="chart(@js($config))" {{ $attributes->merge(['class' => 'relative']) }} style="min-height: {{ $height }}px">
        <div x-ref="canvas" x-show="!empty" class="transition-opacity duration-300" :class="ready ? 'opacity-100' : 'opacity-0'"></div>

        {{-- Skeleton while ApexCharts loads --}}
        <div x-show="!ready" class="absolute inset-0 flex items-end gap-2 px-2 pb-6" aria-hidden="true">
            @foreach ([38, 62, 48, 74, 56, 82, 64, 70, 52, 88, 60, 76] as $h)
                <span class="skeleton flex-1 rounded-b-none" style="height: {{ $h }}%"></span>
            @endforeach
        </div>

        <div x-show="ready && empty" x-cloak class="absolute inset-0 grid place-items-center">
            <div class="text-center">
                <span class="mx-auto mb-2 grid size-10 place-items-center rounded-full bg-subtle text-ink-faint ring-1 ring-line"><x-dashboard.icon name="chart-line" /></span>
                <p class="text-[13px] text-ink-faint">{{ $empty }}</p>
            </div>
        </div>
    </div>
@endif

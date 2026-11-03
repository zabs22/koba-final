@extends('layouts.branch')

@section('title', 'Reports')

@section('content')
@php
    $config = ['endpoint' => route('branch.reports'), 'payload' => $payload];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header eyebrow="Money" title="Reports" description="This café's numbers for any period, ready to export as CSV.">
        <x-slot:actions><x-dashboard.date-range :options="array_diff_key(\App\Support\Dashboard\DateRange::OPTIONS, ['today' => 1])" /></x-slot:actions>
    </x-dashboard.page-header>

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @foreach (['orders', 'revenue', 'prep', 'cancel'] as $key)
                <x-dashboard.kpi-card :metric="$key" :format="$payload['kpis'][$key]['format']" />
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <x-dashboard.card title="Sales" subtitle="This period against the previous one">
                <x-dashboard.chart type="area" source="charts.sales" format="etb" :height="280" />
            </x-dashboard.card>
            <x-dashboard.card title="Best sellers" subtitle="Units">
                <x-dashboard.chart type="hbar" source="charts.products" :height="280" />
            </x-dashboard.card>
        </div>

        <div>
            <h2 class="mb-3 font-display text-[15px] font-semibold">Exports <span class="font-sans text-xs font-normal text-ink-faint" x-text="'· ' + payload.range.label"></span></h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($exports as $type => $export)
                    <a :href="@js(route('branch.reports.export', $type)) + '?' + new URLSearchParams({ range: filters.range, from: filters.from, to: filters.to })"
                       class="card group flex items-start gap-4 p-5 transition-colors hover:border-line-strong">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent"><x-dashboard.icon :name="$export['icon']" /></span>
                        <div class="min-w-0 flex-1"><p class="font-medium">{{ $export['label'] }}</p><p class="mt-0.5 text-xs text-ink-soft">{{ $export['description'] }}</p></div>
                        <x-dashboard.icon name="download" class="size-4 text-ink-faint group-hover:text-accent" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

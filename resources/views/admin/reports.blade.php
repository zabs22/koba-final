@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
@php
    $config = ['endpoint' => route('admin.reports'), 'payload' => $payload];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header eyebrow="Finance" title="Reports & analytics" description="Compare branches and periods, then export what you need as CSV (opens in Excel or Google Sheets).">
        <x-slot:actions><x-dashboard.date-range :options="array_diff_key(\App\Support\Dashboard\DateRange::OPTIONS, ['today' => 1])" /></x-slot:actions>
    </x-dashboard.page-header>

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        <div class="grid gap-5 xl:grid-cols-2">
            <x-dashboard.card title="Branches: this period vs previous" subtitle="Revenue">
                <x-dashboard.chart type="bar" source="charts.compare" format="etb" :height="300" />
            </x-dashboard.card>
            <x-dashboard.card title="Orders per branch" subtitle="Day by day">
                <x-dashboard.chart type="line" source="charts.orders" :height="300" />
            </x-dashboard.card>
        </div>

        <x-dashboard.card title="Branch comparison" :padded="false">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Branch</th><th class="text-right">Orders</th><th class="text-right">Revenue</th><th class="text-right">Previous</th><th class="text-right">Change</th><th class="text-right">Avg. order</th><th class="text-right">Avg. prep</th><th class="text-right">Cancelled</th></tr></thead>
                    <tbody>
                        <template x-for="r in payload.rows" :key="r.branch">
                            <tr>
                                <td class="font-medium" x-text="r.branch"></td>
                                <td class="text-right tabular-nums" x-text="fmt(r.orders)"></td>
                                <td class="text-right font-medium tabular-nums" x-text="fmt(r.revenue, 'etb')"></td>
                                <td class="text-right tabular-nums text-ink-soft" x-text="fmt(r.revenue_prev, 'etb')"></td>
                                <td class="text-right tabular-nums font-medium" :class="r.change >= 0 ? 'text-success' : 'text-danger'" x-text="r.change === null ? '—' : (r.change > 0 ? '+' : '') + r.change + '%'"></td>
                                <td class="text-right tabular-nums" x-text="fmt(r.aov, 'etb')"></td>
                                <td class="text-right tabular-nums" x-text="r.prep + ' min'"></td>
                                <td class="text-right tabular-nums" x-text="r.cancel + '%'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-dashboard.card>

        <div>
            <h2 class="mb-3 font-display text-[15px] font-semibold">Exports <span class="font-sans text-xs font-normal text-ink-faint" x-text="'· ' + payload.range.label"></span></h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($exports as $type => $export)
                    <a :href="@js(route('admin.reports.export', $type)) + '?' + new URLSearchParams({ range: filters.range, from: filters.from, to: filters.to })"
                       class="card group flex items-start gap-4 p-5 transition-colors hover:border-line-strong">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent"><x-dashboard.icon :name="$export['icon']" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $export['label'] }}</p>
                            <p class="mt-0.5 text-xs text-ink-soft">{{ $export['description'] }}</p>
                        </div>
                        <x-dashboard.icon name="download" class="size-4 text-ink-faint group-hover:text-accent" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

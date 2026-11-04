@extends('layouts.admin')

@section('title', 'Platform revenue')

@section('content')
@php
    $config = ['endpoint' => route('admin.revenue'), 'payload' => $payload, 'poll' => 30];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header eyebrow="Finance" title="Platform revenue" description="Gross sales, KOBA's platform commission and what each branch is paid out.">
        <x-slot:actions>
            <a href="{{ route('admin.settings') }}" class="btn-ghost btn-sm"><x-dashboard.icon name="percent" class="size-3.5" /> Commission rate</a>
            <x-dashboard.date-range :options="array_diff_key(\App\Support\Dashboard\DateRange::OPTIONS, ['today' => 1])" />
        </x-slot:actions>
    </x-dashboard.page-header>

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($payload['kpis'] as $key => $kpi)
                <x-dashboard.kpi-card :metric="$key" :format="$kpi['format']" />
            @endforeach
        </div>

        <x-dashboard.card title="Revenue over time" subtitle="This period against the previous one">
            <x-dashboard.chart type="area" source="overview.revenue" format="etb" :height="320" />
        </x-dashboard.card>

        <div class="grid gap-5 xl:grid-cols-5">
            <x-dashboard.card class="xl:col-span-3" title="Revenue by branch">
                <x-dashboard.chart type="stacked" source="overview.byBranch" format="etb" :height="300" :entities="array_keys(app(\App\Services\Demo\DemoDataService::class)->branches())" />
            </x-dashboard.card>

            <x-dashboard.card class="xl:col-span-2" title="Commission & payouts" :padded="false">
                <x-slot:actions><span class="text-xs text-ink-faint" x-text="'Rate ' + (payload.payouts[0]?.rate ?? 0) + '%'"></span></x-slot:actions>
                <table class="table">
                    <thead><tr><th>Branch</th><th class="text-right">Gross</th><th class="text-right">Commission</th><th class="text-right">Payout</th></tr></thead>
                    <tbody>
                        <template x-for="p in payload.payouts" :key="p.key">
                            <tr>
                                <td class="font-medium" x-text="p.branch"></td>
                                <td class="text-right tabular-nums" x-text="fmt(p.gross, 'etb', true)"></td>
                                <td class="text-right tabular-nums text-brown-600 dark:text-sand-300" x-text="fmt(p.commission, 'etb', true)"></td>
                                <td class="text-right font-medium tabular-nums" x-text="fmt(p.net, 'etb', true)"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold">
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right tabular-nums" x-text="fmt(payload.payouts.reduce((s, p) => s + p.gross, 0), 'etb', true)"></td>
                            <td class="px-4 py-3 text-right tabular-nums" x-text="fmt(payload.payouts.reduce((s, p) => s + p.commission, 0), 'etb', true)"></td>
                            <td class="px-4 py-3 text-right tabular-nums" x-text="fmt(payload.payouts.reduce((s, p) => s + p.net, 0), 'etb', true)"></td>
                        </tr>
                    </tfoot>
                </table>
            </x-dashboard.card>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <x-dashboard.card title="Top categories" subtitle="Revenue">
                <x-dashboard.chart type="hbar" source="overview.categories" format="etb" :height="280" />
            </x-dashboard.card>
            <x-dashboard.card title="Payment methods" subtitle="Share of revenue">
                <x-dashboard.chart type="donut" source="overview.payments" format="etb" :height="280" />
            </x-dashboard.card>
        </div>
    </div>
</div>
@endsection

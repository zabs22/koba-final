@extends('layouts.branch')

@section('title', 'Payments')

@section('content')
@php
    $config = ['endpoint' => route('branch.payments'), 'payload' => $payload, 'poll' => 30];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header eyebrow="Money" title="Payments" description="What this café collected, by method. Demo transactions — no real money moves.">
        <x-slot:actions><x-dashboard.date-range /></x-slot:actions>
    </x-dashboard.page-header>

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($payload['kpis'] as $key => $kpi)
                <x-dashboard.kpi-card :metric="$key" :format="$kpi['format']" />
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <x-dashboard.card class="xl:col-span-2" title="Collected" subtitle="Per day (per hour for today)">
                <x-dashboard.chart type="bar" source="charts.volume" format="etb" :height="280" />
            </x-dashboard.card>
            <x-dashboard.card title="Method split">
                <x-dashboard.chart type="donut" source="charts.methods" format="etb" :height="280" />
            </x-dashboard.card>
        </div>

        <x-dashboard.card title="Transactions" :padded="false">
            <div class="max-h-[560px] overflow-auto">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Customer</th><th>Order</th><th>Method</th><th>Time</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        <template x-for="tx in payload.rows.slice(0, 60)" :key="tx.id">
                            <tr class="cursor-pointer" @click="$dispatch('open-order', tx.order)">
                                <td class="font-mono text-[12.5px]" x-text="tx.id"></td>
                                <td><span class="flex items-center gap-2"><span x-html="$avatar(tx.initials, tx.order.length)"></span><span x-text="tx.customer"></span></span></td>
                                <td class="font-mono text-[12.5px] text-ink-soft" x-text="tx.order"></td>
                                <td class="text-ink-soft" x-text="tx.method_label"></td>
                                <td class="text-ink-soft" x-text="tx.time"></td>
                                <td class="text-right font-medium tabular-nums" x-text="fmt(tx.amount, 'etb')"></td>
                                <td x-html="$badge(tx.status)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p x-show="!payload.rows.length" class="px-5 py-10 text-center text-[13px] text-ink-faint">No transactions in this period.</p>
            </div>
            <p class="border-t border-line px-5 py-3 text-xs text-ink-faint" x-show="payload.rows.length > 60" x-text="'Showing the latest 60 of ' + payload.rows.length + ' — export the full list from Reports.'"></p>
        </x-dashboard.card>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
@php
    $config = ['endpoint' => route('admin.payments'), 'payload' => $payload, 'poll' => 30];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header eyebrow="Finance" title="Payments & transactions" description="Telebirr, CBE Birr, Chapa and cash on pickup. These are generated demo transactions — no real money moves.">
        <x-slot:actions><x-dashboard.date-range :options="array_diff_key(\App\Support\Dashboard\DateRange::OPTIONS, ['today' => 1])" /></x-slot:actions>
    </x-dashboard.page-header>

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($payload['kpis'] as $key => $kpi)
                <x-dashboard.kpi-card :metric="$key" :format="$kpi['format']" />
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <x-dashboard.card class="xl:col-span-2" title="Volume by method" subtitle="Collected per day, stacked by payment method">
                <x-dashboard.chart type="stacked" source="charts.volume" format="etb" :height="300" />
            </x-dashboard.card>
            <x-dashboard.card title="Method split" subtitle="Share of collected revenue">
                <x-dashboard.chart type="donut" source="charts.methods" format="etb" :height="300" />
            </x-dashboard.card>
        </div>
    </div>
</div>

<div class="mt-5">
    <x-dashboard.data-table
        :endpoint="route('admin.payments.data')" :per-page="15" sort="placed_at" export-name="transactions" sum
        search="Search reference, order or customer…"
        :filters="[
            'range' => ['label' => 'Period', 'value' => '30d', 'options' => ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month']],
            'method' => ['label' => 'Method', 'options' => array_map(fn ($p) => $p['label'], \App\Services\Demo\DemoCatalog::PAYMENTS)],
            'status' => ['label' => 'Status', 'options' => ['paid' => 'Paid', 'due' => 'Due at pickup', 'refunded' => 'Refunded', 'void' => 'Void']],
            'branch' => ['label' => 'Branch', 'options' => array_column($branches, 'short', 'key')],
        ]"
        :columns="[
            ['key' => 'id', 'label' => 'Reference', 'type' => 'mono'],
            ['key' => 'customer', 'label' => 'Customer', 'type' => 'person', 'sub' => 'order'],
            ['key' => 'branch_name', 'label' => 'Branch'],
            ['key' => 'method_label', 'label' => 'Method', 'type' => 'muted'],
            ['key' => 'time', 'label' => 'Time', 'type' => 'muted'],
            ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
        ]">
        <x-slot:row>
            <tr class="cursor-pointer" @click="$dispatch('open-order', row.order)">
                <template x-for="col in columns" :key="col.key"><td :class="col.align === 'right' && 'text-right'" x-html="render(col, row)"></td></template>
            </tr>
        </x-slot:row>
    </x-dashboard.data-table>
</div>
@endsection

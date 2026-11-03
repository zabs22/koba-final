@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
@php
    $branchKeys = array_keys($branches);
    $config = ['endpoint' => route('admin.dashboard.metrics'), 'payload' => $payload, 'poll' => config('dashboard.poll_seconds'), 'params' => ['branch' => $payload['branch'] ?? '']];
@endphp

<div x-data="page(@js($config))" @chart-select="$event.detail.chart === 'charts.byBranch' && set('branch', $event.detail.key)">
    <x-dashboard.page-header eyebrow="Platform overview" title="Every KOBA café, at a glance">
        <x-slot:description>
            <span x-text="(filters.branch ? @js(array_column($branches, 'short', 'key'))[filters.branch] : 'All branches') + ' · ' + payload.range.label"></span>
        </x-slot:description>
        <x-slot:actions>
            <label class="sr-only" for="branch-filter">Branch</label>
            <select id="branch-filter" x-model="filters.branch" class="field h-9 w-auto py-0 pr-8 text-[13px]">
                <option value="">All branches</option>
                @foreach ($branches as $b)<option value="{{ $b['key'] }}">{{ $b['short'] }}</option>@endforeach
            </select>
            <x-dashboard.date-range />
        </x-slot:actions>
    </x-dashboard.page-header>

    @include('dashboard.partials.live-status')

    <div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
        {{-- KPIs --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($payload['kpis'] as $key => $kpi)
                <x-dashboard.kpi-card :metric="$key" :format="$kpi['format']"
                    :href="match ($key) { 'branches', 'active' => route('admin.branches'), 'customers', 'new_customers' => route('admin.customers'), 'orders' => route('admin.orders'), 'revenue', 'transactions' => route('admin.payments'), default => null }" />
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <x-dashboard.card class="xl:col-span-2" title="Platform revenue" subtitle="This period against the previous one">
                <x-slot:meta>
                    <p class="mt-3 flex items-baseline gap-2">
                        <span class="font-display text-2xl font-semibold tabular-nums" x-text="fmt(kpi('revenue').value, 'etb')"></span>
                        <span class="text-xs text-ink-faint" x-text="payload.range.compare"></span>
                    </p>
                </x-slot:meta>
                <x-dashboard.chart type="area" source="charts.revenue" format="etb" :height="300" />
            </x-dashboard.card>

            <x-dashboard.card title="Order status" subtitle="Click a slice to see those orders">
                <x-dashboard.chart type="donut" source="charts.status" colors="status" :height="300" :href="route('admin.orders').'?status={key}'" />
            </x-dashboard.card>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <x-dashboard.card class="xl:col-span-2" title="Revenue by branch" subtitle="Each café's contribution · click a bar to focus that branch">
                <x-dashboard.chart type="stacked" source="charts.byBranch" format="etb" :height="300" :entities="$branchKeys" />
            </x-dashboard.card>

            <x-dashboard.card title="Payment methods" subtitle="Share of revenue">
                <x-dashboard.chart type="donut" source="charts.payments" format="etb" :height="300" :href="route('admin.payments').'?method={key}'" />
            </x-dashboard.card>
        </div>

        {{-- Branch leaderboard --}}
        <x-dashboard.card title="Branch performance" subtitle="Ranked by revenue for the selected period" :padded="false">
            <x-slot:actions><a href="{{ route('admin.branches') }}" class="btn-ghost btn-sm">All branches <x-dashboard.icon name="arrow-right" class="size-3.5" /></a></x-slot:actions>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th class="w-12">#</th><th>Branch</th><th class="text-right">Orders</th><th class="text-right">Revenue</th><th>Share</th><th class="text-right">Avg. prep</th><th class="text-right">Rating</th><th>Trend</th></tr></thead>
                    <tbody>
                        <template x-for="row in payload.leaderboard" :key="row.key">
                            <tr class="cursor-pointer" @click="window.location = '{{ route('admin.branches') }}/' + row.key">
                                <td><span class="grid size-7 place-items-center rounded-full font-display text-xs font-semibold" :class="row.rank === 1 ? 'bg-sand-400 text-forest-900' : 'bg-subtle text-ink-soft ring-1 ring-line'" x-text="row.rank"></span></td>
                                <td>
                                    <p class="font-medium" x-text="row.name"></p>
                                    <p class="flex items-center gap-1.5 text-xs text-ink-faint"><span x-html="$badge(row.status)"></span><span x-text="row.area"></span></p>
                                </td>
                                <td class="text-right tabular-nums" x-text="fmt(row.orders)"></td>
                                <td class="text-right">
                                    <p class="font-medium tabular-nums" x-text="fmt(row.revenue, 'etb')"></p>
                                    <p class="text-xs tabular-nums" :class="row.change >= 0 ? 'text-success' : 'text-danger'" x-show="row.change !== null" x-text="(row.change > 0 ? '+' : '') + row.change + '%'"></p>
                                </td>
                                <td>
                                    <span class="flex items-center gap-2">
                                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-subtle ring-1 ring-line ring-inset"><span class="block h-full rounded-full bg-forest-500 transition-[width] duration-500" :style="`width: ${row.share}%`"></span></span>
                                        <span class="text-xs tabular-nums text-ink-soft" x-text="row.share + '%'"></span>
                                    </span>
                                </td>
                                <td class="text-right tabular-nums" x-text="row.prep ? row.prep + ' min' : '—'"></td>
                                <td class="text-right tabular-nums"><span class="inline-flex items-center gap-1" x-show="row.rating"><span x-html="$icon('star', 'size-3.5 fill-sand-400 text-sand-500')"></span><span x-text="row.rating"></span></span></td>
                                <td class="w-36"><span class="block h-8 w-32" x-html="$spark(row.spark)"></span></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-dashboard.card>

        <div class="grid gap-5 xl:grid-cols-3">
            <x-dashboard.card class="xl:col-span-2" title="Peak hours" subtitle="Orders by weekday and pickup hour — plan staff around the darkest cells">
                <x-dashboard.chart type="heatmap" source="charts.heatmap" :height="300" />
            </x-dashboard.card>
            <x-dashboard.card title="Top categories" subtitle="Revenue · click to see the products">
                <x-dashboard.chart type="hbar" source="charts.categories" format="etb" :height="300" :href="route('admin.products').'?category={key}'" />
            </x-dashboard.card>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <x-dashboard.card title="Customer growth" subtitle="Customers ordering per period: returning vs first-time">
                <x-dashboard.chart type="stacked" source="charts.customers" :height="280" />
            </x-dashboard.card>

            <x-dashboard.card title="Recent transactions" subtitle="Demo payments — not real money" :padded="false">
                <x-slot:actions><a href="{{ route('admin.payments') }}" class="btn-ghost btn-sm">View all</a></x-slot:actions>
                <ul class="divide-y divide-line">
                    <template x-for="tx in payload.transactions" :key="tx.id">
                        <li class="flex cursor-pointer items-center gap-3 px-5 py-3 hover:bg-subtle" @click="$dispatch('open-order', tx.order)">
                            <span x-html="$avatar(tx.initials, tx.order.length)"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium" x-text="tx.customer"></p>
                                <p class="truncate text-xs text-ink-faint"><span x-text="tx.method_label"></span> · <span x-text="tx.branch_name"></span> · <span x-text="tx.time"></span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[13px] font-medium tabular-nums" x-text="fmt(tx.amount, 'etb')"></p>
                                <span x-html="$badge(tx.status)"></span>
                            </div>
                        </li>
                    </template>
                </ul>
            </x-dashboard.card>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <x-dashboard.card title="New customers" subtitle="First orders, most recent first" :padded="false">
                <ul class="divide-y divide-line">
                    <template x-for="(c, i) in payload.registrations" :key="c.name + i">
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span x-html="$avatar(c.initials, i)"></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-[13px] font-medium" x-text="c.name"></p><p class="text-xs text-ink-faint" x-text="c.branch + ' · ' + c.orders + (c.orders === 1 ? ' order' : ' orders')"></p></div>
                            <span class="shrink-0 text-xs text-ink-faint" x-text="c.when"></span>
                        </li>
                    </template>
                </ul>
            </x-dashboard.card>

            <x-dashboard.card title="Branches right now" subtitle="Live status and today's numbers" :padded="false">
                <ul class="divide-y divide-line">
                    <template x-for="b in payload.branches" :key="b.key">
                        <li>
                            <a :href="'{{ route('admin.branches') }}/' + b.key" class="flex items-center gap-3 px-5 py-3 hover:bg-subtle">
                                <span class="grid size-9 place-items-center rounded-lg bg-forest-900 text-sand-300" x-html="$icon('store', 'size-4')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-2 text-[13px] font-medium"><span x-text="b.name"></span><span x-html="$badge(b.status)"></span></p>
                                    <p class="text-xs text-ink-faint"><span x-text="b.active"></span> active · <span x-text="b.prep ? b.prep + ' min prep' : 'no prep data'"></span></p>
                                </div>
                                <span class="text-[13px] font-medium tabular-nums" x-text="fmt(b.revenue, 'etb', true)"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </x-dashboard.card>

            <x-dashboard.card title="System alerts" :padded="false">
                <x-slot:actions><span class="rounded-full bg-subtle px-2 py-0.5 text-xs font-medium tabular-nums text-ink-soft ring-1 ring-line" x-text="payload.alerts.length"></span></x-slot:actions>
                <ul class="divide-y divide-line">
                    <template x-for="(a, i) in payload.alerts" :key="a.title + i">
                        <li class="flex gap-3 px-5 py-3">
                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full"
                                  :class="{ 'bg-danger-soft text-danger': a.level === 'danger', 'bg-warning-soft text-warning': a.level === 'warning', 'bg-info-soft text-info': a.level === 'info' }"
                                  x-html="$icon({ danger: 'alarm-clock', warning: 'triangle-alert', info: 'info' }[a.level], 'size-3.5')"></span>
                            <div class="min-w-0"><p class="text-[13px] font-medium" x-text="a.title"></p><p class="text-xs text-ink-soft" x-text="a.body"></p></div>
                        </li>
                    </template>
                    <li x-show="!payload.alerts.length" class="px-5 py-8 text-center text-[13px] text-ink-faint">All branches running smoothly.</li>
                </ul>
            </x-dashboard.card>
        </div>
    </div>
</div>
@endsection

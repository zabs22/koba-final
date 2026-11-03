{{--
    One branch's analytics (branch overview and the super admin's branch page).
    Lives inside a `page` component whose payload comes from BranchDashboardService::overview().
--}}
@php
    $area = auth()->user()->role()->area();
    $live = $area === 'branch';
@endphp

<div class="space-y-5 transition-opacity" :class="loading && 'opacity-60'">
    {{-- KPIs: the three "now" figures are live counts from today's board --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($payload['kpis'] as $key => $kpi)
            <x-dashboard.kpi-card :metric="$key" :format="$kpi['format']" :live="in_array($key, ['pending', 'preparing', 'ready'], true)"
                :href="$live && in_array($key, ['pending', 'preparing', 'ready'], true) ? route('branch.live-orders') : null" />
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <x-dashboard.card class="xl:col-span-2" title="Sales" subtitle="This period against the previous one">
            <x-slot:meta>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="font-display text-2xl font-semibold tabular-nums" x-text="fmt(kpi('revenue').value, 'etb')"></span>
                    <span class="text-xs text-ink-faint" x-text="payload.range.compare"></span>
                </p>
            </x-slot:meta>
            <x-dashboard.chart type="area" source="charts.sales" format="etb" :height="290" />
        </x-dashboard.card>

        <x-dashboard.card title="Sales by category" subtitle="Revenue share">
            <x-dashboard.chart type="donut" source="charts.categories" format="etb" :height="290" :href="$live ? route('branch.products').'?category={key}' : route('admin.products').'?category={key}'" />
        </x-dashboard.card>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <x-dashboard.card title="Orders by hour" subtitle="Average per day, by pickup hour — the rush periods">
            <x-dashboard.chart type="bar" source="charts.hours" :height="280" />
        </x-dashboard.card>

        <x-dashboard.card title="Average preparation time" subtitle="Minutes from accepting to ready, against the target">
            <x-dashboard.chart type="line" source="charts.prep" format="minutes" :height="280" />
        </x-dashboard.card>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <x-dashboard.card class="xl:col-span-2" title="Popular products" subtitle="Units sold in the period">
            <div class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-3"><x-dashboard.chart type="hbar" source="charts.products" :height="300" /></div>
                <ol class="space-y-3 lg:col-span-2">
                    <template x-for="(p, i) in payload.top" :key="p.id">
                        <li class="flex items-center gap-3">
                            <span class="w-4 font-display text-xs font-semibold text-ink-faint" x-text="i + 1"></span>
                            <span x-html="$thumb(p.image, p.name, 'croissant')"></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-[13px] font-medium" x-text="p.name"></p><p class="truncate text-xs text-ink-faint" x-text="p.category"></p></div>
                            <div class="text-right"><p class="text-[13px] font-semibold tabular-nums" x-text="p.units + ' sold'"></p><p class="text-xs tabular-nums text-ink-faint" x-text="fmt(p.revenue, 'etb', true)"></p></div>
                        </li>
                    </template>
                </ol>
            </div>
        </x-dashboard.card>

        <x-dashboard.card title="Upcoming pickups" subtitle="Next three hours · kitchen start → pickup">
            <x-dashboard.chart type="timeline" source="charts.pickups" :height="300" empty="No pickups in the next three hours." />
        </x-dashboard.card>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <x-dashboard.card class="xl:col-span-2" title="Recent orders" :padded="false">
            @if ($live)
                <x-slot:actions><a href="{{ route('branch.orders') }}" class="btn-ghost btn-sm">All orders <x-dashboard.icon name="arrow-right" class="size-3.5" /></a></x-slot:actions>
            @endif
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th class="hidden lg:table-cell">Items</th><th class="text-right">Total</th><th>Pickup</th><th>Status</th>@if ($live)<th></th>@endif</tr></thead>
                    <tbody>
                        <template x-for="o in payload.recent" :key="o.id">
                            <tr class="cursor-pointer" @click="$dispatch('open-order', o.id)">
                                <td class="font-mono text-[12.5px]" x-text="o.id"></td>
                                <td><span class="flex items-center gap-2"><span x-html="$avatar(o.initials, o.customer_id)"></span><span class="truncate" x-text="o.customer"></span></span></td>
                                <td class="hidden max-w-[220px] truncate text-ink-soft lg:table-cell" x-text="o.items"></td>
                                <td class="text-right font-medium tabular-nums" x-text="fmt(o.total, 'etb')"></td>
                                <td class="tabular-nums" x-text="o.pickup_time"></td>
                                <td x-html="$badge(o.status)"></td>
                                @if ($live)
                                    <td class="text-right"><button type="button" class="btn-secondary btn-sm" x-show="o.next" @click.stop="$dispatch('open-order', o.id)">Manage</button></td>
                                @endif
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p x-show="!payload.recent.length" class="px-5 py-10 text-center text-[13px] text-ink-faint">No orders in this period.</p>
            </div>
        </x-dashboard.card>

        <div class="space-y-5">
            <x-dashboard.card title="Stock alerts" subtitle="Low, sold out or switched off today" :padded="false">
                @if ($live)
                    <x-slot:actions><a href="{{ route('branch.products') }}" class="btn-ghost btn-sm">Products</a></x-slot:actions>
                @endif
                <ul class="divide-y divide-line">
                    <template x-for="p in payload.stock" :key="p.id">
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <span x-html="$thumb(p.image, p.name, 'package')"></span>
                            <p class="min-w-0 flex-1 truncate text-[13px] font-medium" x-text="p.name"></p>
                            <span x-html="!p.available && p.stock === 'ok' ? $badge('void', 'Switched off') : $badge(p.stock, p.stock === 'low' ? p.left + ' left' : null)"></span>
                        </li>
                    </template>
                    <li x-show="!payload.stock.length" class="px-5 py-6 text-center text-[13px] text-ink-faint">Everything is in stock.</li>
                </ul>
            </x-dashboard.card>

            <x-dashboard.card title="Today's pickup schedule" :padded="false">
                @if ($live)
                    <x-slot:actions><a href="{{ route('branch.pickups') }}" class="btn-ghost btn-sm">Schedule</a></x-slot:actions>
                @endif
                <ol class="relative px-5 py-3">
                    <template x-for="o in payload.schedule" :key="o.id">
                        <li class="relative flex cursor-pointer gap-3 py-2 pl-5 before:absolute before:top-0 before:bottom-0 before:left-[5px] before:w-px before:bg-line" @click="$dispatch('open-order', o.id)">
                            <span class="absolute top-3.5 left-0 size-[11px] rounded-full border-2 border-panel"
                                  :class="{ 'bg-sand-500': o.status === 'pending', 'bg-info': o.status === 'preparing', 'bg-success': o.status === 'ready' }"></span>
                            <span class="w-11 shrink-0 font-display text-[13px] font-semibold tabular-nums" x-text="o.pickup_time"></span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-[13px]" x-text="o.customer"></span><span class="block truncate text-xs text-ink-faint" x-text="o.items"></span></span>
                        </li>
                    </template>
                    <li x-show="!payload.schedule.length" class="py-4 text-center text-[13px] text-ink-faint">No pickups waiting.</li>
                </ol>
            </x-dashboard.card>
        </div>
    </div>
</div>

{{-- Order detail slide-over (admin, branch, customer). Opens on the `open-order` window event or ?order=… --}}
@php
    $config = [
        'showUrl' => route("$area.orders.show", '__ID__'),
        'moveUrl' => $area === 'branch' ? route('branch.orders.status', '__ID__') : null,
        'receiptUrl' => $area === 'branch' ? route('branch.orders.receipt', '__ID__') : null,
    ];
@endphp

<div x-data="orderDrawer(@js($config))" @open-order.window="show($event.detail)" data-order-drawer>
    <x-dashboard.slide-over show="open" close="close()" width="max-w-md">
        <header class="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
            <div class="min-w-0">
                <p class="eyebrow">Order</p>
                <h2 class="mt-0.5 font-mono text-[15px] font-semibold tracking-tight" x-text="order?.id ?? 'Loading…'"></h2>
                <div class="mt-2 flex flex-wrap items-center gap-2" x-show="order">
                    <span x-html="order && $badge(order.status)"></span>
                    <span x-html="order && $badge(order.payment_status)"></span>
                    <span x-show="order?.scheduled" class="text-xs text-ink-faint">Scheduled pre-order</span>
                </div>
            </div>
            <button type="button" class="btn-icon -mr-2" @click="close()" aria-label="Close"><x-dashboard.icon name="x" /></button>
        </header>

        <div class="flex-1 overflow-y-auto">
            <template x-if="loading">
                <div class="space-y-4 p-5">@for ($i = 0; $i < 5; $i++)<div class="skeleton h-12"></div>@endfor</div>
            </template>

            <template x-if="order && !loading">
                <div class="divide-y divide-line">
                    {{-- Progress --}}
                    <section class="px-5 py-4">
                        <ol class="grid grid-cols-4 gap-2">
                            <template x-for="step in order.timeline" :key="step.status">
                                <li>
                                    <span class="block h-1.5 rounded-full" :class="step.done ? (order.status === 'cancelled' ? 'bg-danger' : 'bg-forest-500') : 'bg-ink/10'"></span>
                                    <span class="mt-2 block text-[11.5px] font-medium" :class="step.done ? 'text-ink' : 'text-ink-faint'" x-text="step.label"></span>
                                    <span class="block text-[11px] tabular-nums text-ink-faint" x-text="step.time ?? '—'"></span>
                                </li>
                            </template>
                        </ol>
                    </section>

                    <section class="grid grid-cols-2 gap-4 px-5 py-4 text-[13px]">
                        <div><p class="text-xs text-ink-faint">Customer</p><p class="mt-0.5 font-medium" x-text="order.customer"></p><p class="text-xs text-ink-soft" x-text="order.customer_phone"></p></div>
                        <div><p class="text-xs text-ink-faint">Pickup</p><p class="mt-0.5 font-medium tabular-nums" x-text="order.pickup_time + ' · ' + order.pickup_date"></p><p class="text-xs text-ink-soft" x-text="order.branch_full"></p></div>
                        <div><p class="text-xs text-ink-faint">Placed</p><p class="mt-0.5 tabular-nums" x-text="order.placed"></p></div>
                        <div><p class="text-xs text-ink-faint">Payment</p><p class="mt-0.5" x-text="order.payment_label"></p><p class="font-mono text-[11px] text-ink-faint" x-text="order.reference"></p></div>
                        <div class="col-span-2"><p class="text-xs text-ink-faint">Pickup code</p><p class="mt-0.5 font-mono text-[15px] font-semibold tracking-[0.12em]" x-text="order.pickup_code_display"></p></div>
                        <div class="col-span-2 rounded-lg border border-warning/40 bg-warning-soft px-3 py-2 text-warning" x-show="order.notes"><p class="text-xs font-semibold">Special instructions</p><p class="mt-0.5" x-text="order.notes"></p></div>
                    </section>

                    <section class="px-5 py-4">
                        <p class="mb-3 text-xs font-medium text-ink-faint">Items</p>
                        <ul class="space-y-3">
                            <template x-for="line in order.lines" :key="line.id">
                                <li class="flex items-center gap-3">
                                    <span x-html="$thumb(line.image, line.name, 'croissant')"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-medium" x-text="line.name"></p>
                                        <p class="text-xs text-ink-faint"><span x-text="line.qty"></span> × <span class="tabular-nums" x-text="$fmt(line.price, 'etb')"></span></p>
                                    </div>
                                    <p class="text-[13px] font-medium tabular-nums" x-text="$fmt(line.subtotal, 'etb')"></p>
                                </li>
                            </template>
                        </ul>
                        <div class="mt-4 flex items-center justify-between border-t border-dashed border-line pt-3">
                            <p class="text-[13px] text-ink-soft">Total</p>
                            <p class="font-display text-lg font-semibold tabular-nums" x-text="$fmt(order.total, 'etb')"></p>
                        </div>
                    </section>

                    <template x-if="order.rating">
                        <section class="flex items-center gap-2 px-5 py-4 text-[13px] text-ink-soft">
                            <span x-html="$icon('star', 'size-4 fill-sand-400 text-sand-500')"></span> Rated <span class="font-medium text-ink" x-text="order.rating + ' / 5'"></span>
                        </section>
                    </template>
                </div>
            </template>
        </div>

        <footer class="flex flex-wrap items-center gap-2 border-t border-line px-5 py-4" x-show="order && !loading">
            @if ($area === 'branch')
                <button type="button" class="btn-primary flex-1" x-show="order?.next" :disabled="saving" @click="move(order.next)">
                    <span x-html="$icon(saving ? 'loader-circle' : 'arrow-right', 'size-4' + (saving ? ' animate-spin' : ''))"></span><span x-text="nextLabel()"></span>
                </button>
                <button type="button" class="btn-secondary" x-show="order?.can_cancel" :disabled="saving"
                        @click="$dispatch('confirm', { title: 'Cancel ' + order.id + '?', body: 'The customer is notified and any digital payment is refunded.', confirm: 'Cancel order', danger: true, then: () => move('cancelled') })">Cancel order</button>
                <button type="button" class="btn-icon" @click="receipt()" aria-label="Print receipt" title="Print receipt"><x-dashboard.icon name="printer" /></button>
            @elseif ($area === 'customer')
                <button type="button" class="btn-primary flex-1" @click="reorder()"><x-dashboard.icon name="repeat" /> Reorder</button>
                <a :href="order?.maps" target="_blank" rel="noopener" class="btn-secondary"><x-dashboard.icon name="map-pin" /> Directions</a>
            @else
                <p class="text-xs text-ink-faint">Status changes are made by the branch on its Live Orders board.</p>
            @endif
        </footer>
    </x-dashboard.slide-over>
</div>

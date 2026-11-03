@extends('layouts.branch')

@section('title', 'Live orders')

@section('content')
@php
    $config = [
        'feedUrl' => route('branch.live-orders.feed'),
        'moveUrl' => route('branch.orders.status', '__ID__'),
        'simulateUrl' => route('branch.live-orders.simulate'),
        'initial' => $feed,
        'poll' => config('dashboard.poll_seconds'),
    ];
    $columns = [
        'pending' => ['New', 'bg-sand-500', 'Accept and start when the kitchen is ready.'],
        'preparing' => ['Preparing', 'bg-info', 'In the kitchen now.'],
        'ready' => ['Ready', 'bg-success', 'Waiting at the counter for the customer.'],
        'completed' => ['Picked up', 'bg-ink-faint', 'Collected in the last while.'],
    ];
@endphp

<div x-data="liveBoard(@js($config))">
    <x-dashboard.page-header eyebrow="Kitchen board" title="Live orders">
        <x-slot:description>
            <span class="inline-flex items-center gap-2">
                <span class="live-dot"></span>
                Refreshes every {{ config('dashboard.poll_seconds') }} seconds ·
                <span x-text="syncing ? 'syncing…' : 'last synced ' + syncedAgo() + 's ago'"></span>
            </span>
        </x-slot:description>
        <x-slot:actions>
            <button type="button" class="btn-secondary" @click="toggleSound()" :aria-pressed="sound">
                <span x-html="$icon(sound ? 'volume-2' : 'volume-x', 'size-4')"></span><span x-text="sound ? 'Sound on' : 'Sound off'"></span>
            </button>
            <button type="button" class="btn-secondary" @click="refresh()" :disabled="syncing"><span x-html="$icon('refresh-cw', 'size-4' + (syncing ? ' animate-spin' : ''))"></span> Refresh</button>
            <button type="button" class="btn-primary" @click="simulate()" title="Demo: a customer places an order now">
                <x-dashboard.icon name="plus" /> Simulate order
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <div x-show="status !== 'open'" x-cloak class="mb-4 flex items-center gap-3 rounded-xl border border-warning/30 bg-warning-soft px-4 py-3 text-[13px] text-warning">
        <x-dashboard.icon name="triangle-alert" />
        <span>This branch is <strong x-text="status"></strong> — no new pre-orders are coming in. Change it from the status button in the header.</span>
    </div>

    <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="grid min-w-[1040px] grid-cols-4 gap-4">
            @foreach ($columns as $status => [$label, $dot, $hint])
                <section class="flex min-h-[60vh] flex-col rounded-xl border border-line bg-subtle/60" aria-label="{{ $label }}">
                    <header class="flex items-center gap-2 border-b border-line px-4 py-3">
                        <span class="size-2 rounded-full {{ $dot }}"></span>
                        <h2 class="font-display text-[14px] font-semibold">{{ $label }}</h2>
                        <span class="rounded-full bg-panel px-2 text-xs font-semibold tabular-nums text-ink-soft ring-1 ring-line" x-text="columns.{{ $status }}.length"></span>
                        <span class="ml-auto truncate text-[11px] text-ink-faint" title="{{ $hint }}">{{ $hint }}</span>
                    </header>

                    <div class="flex-1 space-y-3 p-3">
                        <template x-for="order in columns.{{ $status }}" :key="order.id">
                            <article class="rounded-xl border bg-panel p-3.5 shadow-[var(--shadow-card)] transition-colors"
                                     :class="{
                                        'border-line': urgency(order) === 'ok',
                                        'border-warning/50': urgency(order) === 'soon',
                                        'border-danger/60': urgency(order) === 'late',
                                        'is-fresh': fresh.includes(order.id),
                                        'opacity-75': order.status === 'completed',
                                     }">
                                <div class="flex items-center justify-between gap-2">
                                    <button type="button" class="font-mono text-[12.5px] font-semibold tracking-tight hover:underline" @click="$dispatch('open-order', order.id)" x-text="order.id"></button>
                                    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-mono text-[11.5px] tabular-nums"
                                          :class="{ 'bg-subtle text-ink-soft': urgency(order) === 'ok', 'bg-warning-soft text-warning': urgency(order) === 'soon', 'bg-danger-soft text-danger': urgency(order) === 'late' }"
                                          :title="'Since placed'">
                                        <span x-html="$icon('timer', 'size-3')"></span><span x-text="elapsed(order)"></span>
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center gap-2.5">
                                    <span x-html="$avatar(order.initials, order.customer_id)"></span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13.5px] font-medium" x-text="order.customer"></p>
                                        <p class="text-[11.5px] text-ink-faint"><span x-text="order.items_count"></span> items · <span class="tabular-nums" x-text="$fmt(order.total, 'etb')"></span></p>
                                    </div>
                                    <span x-show="fresh.includes(order.id)" class="ml-auto rounded-full bg-sand-400 px-2 py-0.5 text-[10.5px] font-bold text-forest-900 uppercase">New</span>
                                </div>

                                <p class="mt-2.5 line-clamp-2 text-[12.5px] leading-relaxed text-ink-soft" x-text="order.items"></p>

                                @if ($status === 'ready')
                                    <a :href="'{{ route('branch.verify') }}?code=' + order.pickup_code" class="mt-2.5 inline-flex items-center gap-1.5 rounded-md bg-subtle px-2 py-1 font-mono text-[11.5px] font-semibold ring-1 ring-line hover:ring-accent" title="Verify this pickup">
                                        <span x-html="$icon('scan-qr-code', 'size-3.5 text-accent')"></span><span x-text="order.pickup_code"></span>
                                    </a>
                                @endif

                                <div class="mt-3 flex items-center justify-between gap-2 border-t border-dashed border-line pt-2.5 text-[12px]">
                                    <span class="inline-flex items-center gap-1.5 font-medium">
                                        <span x-html="$icon('clock', 'size-3.5 text-ink-faint')"></span>
                                        <span class="tabular-nums" x-text="order.pickup_time"></span>
                                        <span x-show="order.scheduled" class="rounded bg-subtle px-1 text-[10.5px] font-normal text-ink-faint ring-1 ring-line">pre-order</span>
                                    </span>
                                    <span class="tabular-nums" :class="{ 'text-ink-faint': urgency(order) === 'ok', 'text-warning font-medium': urgency(order) === 'soon', 'text-danger font-semibold': urgency(order) === 'late' }" x-text="due(order)"></span>
                                </div>

                                @if ($status !== 'completed')
                                    <div class="mt-3 flex gap-2">
                                        <button type="button" class="btn-primary btn-sm flex-1" :disabled="busy.includes(order.id)" @click="move(order)">
                                            <span x-html="$icon(busy.includes(order.id) ? 'loader-circle' : '{{ ['pending' => 'chef-hat', 'preparing' => 'package-check', 'ready' => 'circle-check'][$status] }}', 'size-3.5' + (busy.includes(order.id) ? ' animate-spin' : ''))"></span>
                                            <span x-text="action(order.status)"></span>
                                        </button>
                                        <button type="button" class="btn-secondary btn-sm px-2" @click="$dispatch('open-order', order.id)" aria-label="Order details"><span x-html="$icon('ellipsis', 'size-4')"></span></button>
                                    </div>
                                @endif
                            </article>
                        </template>

                        <div x-show="!columns.{{ $status }}.length" class="grid place-items-center rounded-xl border border-dashed border-line px-4 py-10 text-center text-[12.5px] text-ink-faint">
                            {{ ['pending' => 'No new orders. Use “Simulate order” to try the flow.', 'preparing' => 'Nothing cooking right now.', 'ready' => 'Nothing waiting at the counter.', 'completed' => 'No pickups yet.'][$status] }}
                        </div>

                        @if ($status === 'pending')
                            <p x-show="later" x-cloak class="flex items-center gap-1.5 px-1 text-xs text-ink-faint">
                                <x-dashboard.icon name="calendar-clock" class="size-3.5" />
                                <span x-text="later + ' more pre-orders scheduled later today'"></span>
                                <a href="{{ route('branch.pickups') }}" class="ml-auto font-medium text-accent hover:underline">Schedule</a>
                            </p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    {{-- Live board changes also refresh any open drawer/table --}}
    <div @table:reload.window="refresh()"></div>
</div>
@endsection

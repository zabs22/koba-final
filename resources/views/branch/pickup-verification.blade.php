@extends('layouts.branch')

@section('title', 'Pickup verification')

@section('content')
@php
    $config = [
        'urls' => [
            'lookup' => route('branch.verify.lookup'),
            'confirm' => route('branch.verify.confirm', '__ID__'),
            'undo' => route('branch.verify.undo', '__ID__'),
            'feed' => route('branch.verify.feed'),
            'reset' => route('branch.verify.reset'),
        ],
        'feed' => $feed,
        'code' => $code,
        'poll' => config('dashboard.poll_seconds'),
    ];
@endphp

<div x-data="pickupVerification(@js($config))" @pointerdown="touch()" @keydown="touch()" :class="counter && 'text-[15px]'">
    <x-dashboard.page-header eyebrow="At the counter" title="Pickup verification" description="Scan the customer’s QR or type their KB code. Finding an order never completes it — you confirm the hand-over.">
        <x-slot:actions>
            @if (config('dashboard.demo_accounts_enabled'))
                <x-pickup.demo-panel />
            @endif
            <button type="button" class="btn-secondary" @click="toggleCounter()" :aria-pressed="counter" title="Hide the sidebar and enlarge everything for a tablet at the counter">
                <x-dashboard.icon name="tablet" class="size-4" /> <span x-text="counter ? 'Exit counter mode' : 'Counter mode'"></span>
            </button>
            <a href="{{ route('branch.live-orders') }}" class="btn-secondary"><x-dashboard.icon name="flame" class="size-4" /> Live orders</a>
        </x-slot:actions>
    </x-dashboard.page-header>

    {{-- Counters --}}
    <div class="mb-5 grid grid-cols-3 gap-3">
        @foreach ([['ready', 'Ready now', 'package-check', 'text-success'], ['picked_up', 'Picked up today', 'circle-check', 'text-ink-soft'], ['late', 'Late pickups', 'alarm-clock', 'text-warning']] as [$key, $label, $icon, $tone])
            <div class="card flex items-center gap-3 px-4 py-3">
                <x-dashboard.icon :name="$icon" class="size-5 {{ $tone }}" />
                <div>
                    <p class="font-display text-[22px] leading-none font-semibold tabular-nums" x-text="feed.counts.{{ $key }}"></p>
                    <p class="mt-1 text-[12px] text-ink-faint">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,440px)_minmax(0,1fr)]">
        {{-- Scanner and code side by side when the card is wide (tablet, counter mode), stacked in the narrow column --}}
        <section class="card grid gap-x-6 p-5 md:grid-cols-2 xl:grid-cols-1" aria-label="Find the order">
            <x-pickup.scanner />
            <div class="my-5 flex items-center gap-3 text-[11px] font-semibold tracking-[0.14em] text-ink-faint uppercase md:hidden xl:flex" aria-hidden="true"><span class="h-px flex-1 bg-line"></span>or<span class="h-px flex-1 bg-line"></span></div>
            <x-pickup.code-input class="md:self-center xl:self-auto" />
        </section>

        <x-pickup.order-result />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <x-dashboard.card title="Today’s ready orders" subtitle="Tap Verify when the customer is at the counter" :padded="false">
            <ul class="divide-y divide-line">
                <template x-for="row in feed.ready" :key="row.id">
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span x-html="$avatar(row.initials, row.customer_id)"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-medium" x-text="row.customer"></p>
                            <p class="text-xs text-ink-faint"><span class="font-mono" x-text="row.id"></span> · <span class="font-mono" x-text="row.pickup_code"></span></p>
                        </div>
                        <span class="text-[13px] font-medium tabular-nums" :class="row.pickup_at < feed.now ? 'text-warning' : 'text-ink-soft'" x-text="row.pickup_time"></span>
                        <button type="button" class="btn-secondary btn-sm" @click="verify(row)" :disabled="busy"><x-dashboard.icon name="scan-qr-code" class="size-3.5" /> Verify</button>
                    </li>
                </template>
                <li x-show="!feed.ready.length"><x-dashboard.empty-state icon="package-check" title="Nothing waiting at the counter" body="Orders appear here when the kitchen marks them Ready." /></li>
            </ul>
        </x-dashboard.card>

        <x-dashboard.card title="Recent verifications" subtitle="Pickups confirmed at this counter today" :padded="false">
            <ul class="divide-y divide-line">
                <template x-for="row in feed.recent" :key="row.id">
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span x-html="$avatar(row.initials, row.customer_id)"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-medium" x-text="row.customer"></p>
                            <p class="text-xs text-ink-faint"><button type="button" class="font-mono hover:underline" @click="$dispatch('open-order', row.id)" x-text="row.id"></button> · <span x-text="'by ' + row.by"></span> · <span x-text="{ qr: 'QR', code: 'code', list: 'list' }[row.via]"></span></p>
                        </div>
                        <span class="text-[13px] tabular-nums text-ink-soft" x-text="row.time"></span>
                    </li>
                </template>
                <li x-show="!feed.recent.length"><x-dashboard.empty-state icon="circle-check" title="No pickups confirmed yet" body="Confirmed pickups show here with the time and who confirmed them." /></li>
            </ul>
        </x-dashboard.card>
    </div>

    <x-pickup.confirm-modal />

    <p class="sr-only" role="status" aria-live="assertive" x-text="announcement"></p>
</div>
@endsection

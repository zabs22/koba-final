@extends('layouts.customer')

@section('title', 'Your KOBA')

@section('content')
@php
    $user = auth()->user();
    $hour = (int) now(config('dashboard.timezone'))->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $stats = $payload['stats'];
    $next = $payload['next'];
    $tracker = ['endpoint' => route('customer.orders.active'), 'demoUrl' => route('customer.orders.demo'), 'initial' => $payload['active'], 'now' => $now, 'poll' => config('dashboard.poll_seconds')];
@endphp

{{-- Greeting --}}
<section class="relative mb-6 overflow-hidden rounded-2xl bg-forest-900 px-6 py-7 text-cream sm:px-8">
    <div class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="text-[11px] font-semibold tracking-[0.16em] text-sand-400 uppercase">{{ $greeting }}</p>
            <h1 class="mt-1.5 font-display text-[28px] leading-tight font-semibold tracking-tight sm:text-[32px]">{{ strtok($user->name, ' ') }}, what's it going to be today?</h1>
            <p class="mt-2 max-w-xl text-[14px] text-cream/70">
                @if ($next)
                    Your next pickup is <strong class="font-semibold text-cream">{{ $next['pickup_time'] }}</strong> at {{ $next['branch_full'] }}.
                @else
                    Order ahead, skip the queue, and pick up at your favourite KOBA café.
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('menu') }}" class="btn bg-sand-400 text-forest-900 hover:bg-sand-300"><x-dashboard.icon name="shopping-bag" /> Browse the menu</a>
            <a href="{{ route('customer.orders') }}" class="btn border border-white/15 text-cream hover:bg-white/5">My orders</a>
        </div>
    </div>
</section>

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Live order tracker --}}
    <section x-data="tracker(@js($tracker))" class="card overflow-hidden lg:col-span-2" aria-live="polite">
        <template x-if="order">
            <div>
                <div class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5">
                    <div>
                        <p class="flex items-center gap-2 text-xs text-ink-faint"><span class="live-dot" x-show="order.status !== 'completed'"></span> Active order · <span class="font-mono" x-text="'#' + order.id"></span> · <span x-text="order.branch_full"></span></p>
                        <h2 class="mt-1 font-display text-[22px] font-semibold tracking-tight" x-text="headline()"></h2>
                        <p class="mt-0.5 text-[13.5px] text-ink-soft" x-text="countdown()"></p>
                        <x-pickup.status-badge status="order.status" class="mt-2" />
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-ink-faint">Pickup</p>
                        <p class="font-display text-[22px] font-semibold tabular-nums" x-text="'Today · ' + order.pickup_time"></p>
                        <p class="text-xs text-ink-soft" x-text="order.branch_full"></p>
                    </div>
                </div>

                <x-pickup.status-timeline class="mx-5 mt-6 mb-2" />

                {{-- Pickup pass: code + QR, one tap from the dashboard --}}
                <div x-show="['pending', 'preparing', 'ready'].includes(order.status) && order.qr" class="mx-5 mt-4 flex flex-wrap items-center gap-4 rounded-xl p-4 ring-1"
                     :class="order.status === 'ready' ? 'bg-success-soft ring-success/30' : 'bg-subtle ring-line'">
                    <button type="button" @click="$dispatch('open-pickup-qr', pass)" class="size-24 shrink-0 rounded-lg bg-white p-1.5 ring-1 ring-line [&_svg]:size-full" aria-label="Show the pickup QR full screen" x-html="order.qr"></button>
                    <x-pickup.code-display compact class="min-w-0 flex-1" />
                    <div class="flex w-full flex-col gap-2 sm:w-auto">
                        <button type="button" class="btn-primary" @click="$dispatch('open-pickup-qr', pass)"><x-dashboard.icon name="qr-code" class="size-4" /> Show QR code</button>
                        <button type="button" class="btn-secondary" @click="$dispatch('open-order', order.id)"><x-dashboard.icon name="receipt-text" class="size-4" /> View order</button>
                    </div>
                </div>

                {{-- Collected: reorder and rate (visual only for now) --}}
                <div x-show="order.status === 'completed'" class="mx-5 mt-4 flex flex-wrap items-center gap-4 rounded-xl bg-subtle p-4 ring-1 ring-line">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">Completed — enjoy your order! ☕</p>
                        <div class="mt-1 flex items-center gap-1" role="group" aria-label="Rate your order">
                            <span class="mr-1 text-[12.5px] text-ink-soft">Rate your order</span>
                            <template x-for="star in 5" :key="star">
                                <button type="button" class="grid size-8 place-items-center rounded-md hover:bg-panel" @click="rate(star)" :aria-label="star + ' star' + (star > 1 ? 's' : '')" :aria-pressed="rating >= star"
                                        x-html="$icon('star', 'size-5 ' + (rating >= star ? 'fill-sand-400 text-sand-500' : 'text-ink-faint'))"></button>
                            </template>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary" @click="$reorder(order.lines.map((line) => line.cart))"><x-dashboard.icon name="repeat" class="size-4" /> Reorder</button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-line bg-subtle px-5 py-4">
                    <div class="flex -space-x-2">
                        <template x-for="line in order.lines.slice(0, 3)" :key="line.id">
                            <span class="rounded-lg ring-2 ring-subtle" x-html="$thumb(line.image, line.name, 'coffee')"></span>
                        </template>
                    </div>
                    <p class="min-w-0 flex-1 truncate text-[13px] text-ink-soft" x-text="order.items"></p>
                    <p class="font-display font-semibold tabular-nums" x-text="$fmt(order.total, 'etb')"></p>
                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-order', order.id)">Details</button>
                    <a :href="order.maps" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-dashboard.icon name="navigation" class="size-3.5" /> Directions</a>
                </div>
            </div>
        </template>

        <template x-if="!order">
            <div class="flex flex-col items-center px-6 py-12 text-center">
                <span class="mb-3 grid size-12 place-items-center rounded-full bg-sand-100 text-brown-600 dark:bg-sand-400/15 dark:text-sand-300"><x-dashboard.icon name="coffee" class="size-5" /></span>
                <h2 class="font-display text-lg font-semibold">No order in the kitchen</h2>
                <p class="mt-1 max-w-sm text-[13px] text-ink-soft">When you pre-order, you can follow it here from the moment it's placed until it's ready at the counter.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('menu') }}" class="btn-primary"><x-dashboard.icon name="shopping-bag" /> Order from the menu</a>
                    <button type="button" class="btn-secondary" @click="placeDemo()" :disabled="placing"><x-dashboard.icon name="sparkles" /> Place a demo order</button>
                </div>
                <p class="mt-3 text-xs text-ink-faint">Demo orders appear on the café's Live Orders board.</p>
            </div>
        </template>
    </section>

    {{-- Next pickup --}}
    <section class="card flex flex-col p-5" x-data="{ at: {{ $next['pickup_at'] ?? 0 }}, now: {{ $now }}, init() { setInterval(() => this.now++, 1000) } }">
        <p class="eyebrow">Next pickup</p>
        @if ($next)
            <p class="mt-3 font-display text-[34px] leading-none font-semibold tabular-nums">{{ $next['pickup_time'] }}</p>
            <p class="mt-1 text-[13px] text-ink-soft" x-text="at > now ? 'in ' + (Math.floor((at - now) / 3600) ? Math.floor((at - now) / 3600) + ' h ' : '') + Math.floor(((at - now) % 3600) / 60) + ' min' : 'Ready when you are'"></p>
            <div class="mt-5 space-y-2.5 text-[13px]">
                <p class="flex items-start gap-2.5"><x-dashboard.icon name="store" class="mt-0.5 text-ink-faint" /><span><span class="font-medium">{{ $next['branch_full'] }}</span><br><span class="text-ink-soft">{{ $next['branch_area'] }}</span></span></p>
                <p class="flex items-center gap-2.5"><x-dashboard.icon name="phone" class="text-ink-faint" /><a href="tel:{{ str_replace(' ', '', $next['branch_phone']) }}" class="hover:underline">{{ $next['branch_phone'] }}</a></p>
                <p class="flex items-center gap-2.5"><x-dashboard.icon name="receipt-text" class="text-ink-faint" /><span class="font-mono text-[12.5px]">{{ $next['id'] }}</span></p>
            </div>
            <a href="{{ $next['maps'] }}" target="_blank" rel="noopener" class="btn-secondary mt-6 w-full"><x-dashboard.icon name="map-pin" /> Open in Google Maps</a>
        @else
            <x-dashboard.empty-state icon="calendar-clock" title="Nothing scheduled" body="Schedule a pickup for later today when you order." class="!py-6" />
        @endif
    </section>
</div>

{{-- Stats --}}
<div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="card p-4">
        <p class="flex items-center gap-2 text-[13px] text-ink-soft"><x-dashboard.icon name="receipt-text" class="text-accent" /> Orders</p>
        <p class="mt-3 font-display text-[25px] font-semibold tabular-nums">{{ number_format($stats['orders']) }}</p>
        <p class="text-xs text-ink-faint">since you joined</p>
    </div>
    <div class="card p-4">
        <p class="flex items-center gap-2 text-[13px] text-ink-soft"><x-dashboard.icon name="wallet" class="text-accent" /> Total spent</p>
        <p class="mt-3 font-display text-[25px] font-semibold tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($stats['spent'], 0) }}</p>
        <p class="text-xs text-ink-faint">all cafés</p>
    </div>
    <div class="card p-4">
        <p class="flex items-center gap-2 text-[13px] text-ink-soft"><x-dashboard.icon name="heart" class="text-accent" /> Favourite café</p>
        <p class="mt-3 truncate font-display text-[25px] font-semibold">{{ $stats['cafe'] }}</p>
        <p class="text-xs text-ink-faint">{{ $stats['cafe_visits'] }} visits</p>
    </div>
    <div class="card relative overflow-hidden p-4">
        <p class="flex items-center gap-2 text-[13px] text-ink-soft"><x-dashboard.icon name="award" class="text-brown-500 dark:text-sand-400" /> KOBA points</p>
        <p class="mt-3 font-display text-[25px] font-semibold tabular-nums">{{ number_format($stats['points']) }}</p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-subtle ring-1 ring-line ring-inset"><div class="h-full rounded-full bg-sand-500" style="width: {{ 100 - $stats['next_reward'] / 25 }}%"></div></div>
        <p class="mt-1 text-xs text-ink-faint">{{ number_format($stats['next_reward']) }} points to a free pastry (demo)</p>
    </div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-dashboard.card class="lg:col-span-2" title="Your spending" subtitle="Per month, in ETB">
        <x-dashboard.chart type="area" :data="$payload['charts']['spending']" format="etb" :height="260" />
    </x-dashboard.card>
    <x-dashboard.card title="What you order most" subtitle="Items by category">
        <x-dashboard.chart type="donut" :data="$payload['charts']['categories']" :height="260" />
    </x-dashboard.card>
</div>

{{-- Recent orders --}}
<x-dashboard.card class="mt-5" title="Recent orders" :padded="false">
    <x-slot:actions><a href="{{ route('customer.orders') }}" class="btn-ghost btn-sm">All orders <x-dashboard.icon name="arrow-right" class="size-3.5" /></a></x-slot:actions>
    <ul class="divide-y divide-line">
        @forelse ($payload['recent'] as $order)
            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5">
                <div class="flex -space-x-2">
                    @foreach (array_slice($order['lines'], 0, 3) as $line)
                        @if ($line['image'])
                            <img src="{{ $line['image'] }}" alt="" loading="lazy" class="size-10 rounded-lg object-cover ring-2 ring-panel">
                        @else
                            <span class="grid size-10 place-items-center rounded-lg bg-forest-900 text-sand-300 ring-2 ring-panel"><x-dashboard.icon name="coffee" /></span>
                        @endif
                    @endforeach
                </div>
                <button type="button" class="min-w-0 flex-1 text-left" x-data @click="$dispatch('open-order', @js($order['id']))">
                    <span class="block truncate text-[13.5px] font-medium">{{ $order['items'] }}</span>
                    <span class="block text-xs text-ink-faint">{{ $order['pickup_date'] }} · {{ $order['branch_name'] }} · <span class="font-mono">{{ $order['id'] }}</span></span>
                </button>
                <x-dashboard.status :value="$order['status']" />
                <span class="w-28 text-right font-medium tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($order['total']) }}</span>
                <button type="button" class="btn-secondary btn-sm" x-data @click="$reorder(@js(array_column($order['lines'], 'cart')))"><x-dashboard.icon name="repeat" class="size-3.5" /> Reorder</button>
            </li>
        @empty
            <li><x-dashboard.empty-state icon="receipt-text" title="No orders yet" body="Your first pre-order will show up here." /></li>
        @endforelse
    </ul>
</x-dashboard.card>

{{-- Favourites --}}
@php $favorites = $payload['favorites']; @endphp
<section class="mt-8">
    <div class="mb-3 flex items-end justify-between">
        <h2 class="font-display text-[17px] font-semibold">Your cafés</h2>
        <a href="{{ route('customer.favorites') }}" class="text-[13px] font-medium text-accent hover:underline">Manage favourites</a>
    </div>
    <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
        @foreach (collect($favorites['branches'])->sortByDesc('favorite') as $b)
            <article class="card w-72 shrink-0 snap-start overflow-hidden">
                <div class="relative h-32 bg-forest-900">
                    <img src="{{ $b['image'] }}" alt="" loading="lazy" class="size-full object-cover opacity-85">
                    @if ($b['favorite'])<span class="absolute top-2.5 left-2.5 grid size-7 place-items-center rounded-full bg-panel/90 text-danger"><x-dashboard.icon name="heart" class="size-3.5 fill-current" /></span>@endif
                </div>
                <div class="p-4">
                    <p class="flex items-center justify-between gap-2 font-medium">{{ $b['name'] }} <x-dashboard.status :value="$b['status']" /></p>
                    <p class="mt-0.5 text-xs text-ink-faint">{{ $b['area'] }} · {{ $b['hours'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>

<section class="mt-6">
    <h2 class="mb-3 font-display text-[17px] font-semibold">Your usuals</h2>
    <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
        @foreach (collect($favorites['products'])->where('favorite', true) as $p)
            <article class="card w-48 shrink-0 snap-start overflow-hidden">
                @if ($p['image'])
                    <img src="{{ $p['image'] }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
                @else
                    <div class="grid aspect-square w-full place-items-center bg-forest-900 text-sand-300/80"><x-dashboard.icon :name="$p['icon']" class="size-9" /></div>
                @endif
                <div class="p-3">
                    <p class="truncate text-[13.5px] font-medium">{{ $p['name'] }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[13px] tabular-nums text-ink-soft">{{ \App\Services\Dashboard\OrderPresenter::etb($p['price'], 0) }}</span>
                        <button type="button" class="btn-icon size-8 bg-accent-soft text-accent" x-data @click="$reorder([@js($p['cart'])])" aria-label="Add {{ $p['name'] }} to your mesob"><x-dashboard.icon name="plus" /></button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection

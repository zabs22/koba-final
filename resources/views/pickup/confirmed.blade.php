{{-- Order confirmed: pickup code + QR after the website's cake order form. Reached by a signed link; works for guests. --}}
@extends('layouts.dashboard')

@section('title', 'Order confirmed')

@section('body')
@php
    $user = auth()->user();
    $tracker = ['endpoint' => $statusUrl, 'initial' => $order, 'now' => $now, 'poll' => config('dashboard.poll_seconds')];
    $hours = $branch ? sprintf('Mon–Fri %02d:00–%02d:00 · Sat–Sun %02d:00–%02d:00', ...[...$branch['hours']['weekday'], ...$branch['hours']['weekend']]) : null;
@endphp

<div x-data="tracker(@js($tracker))" class="min-h-full pb-16">
    <header class="border-b border-line bg-canvas/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}" aria-label="KOBA home"><x-site.logo class="h-[30px] w-auto" /></a>
            <a href="{{ $user ? route('dashboard') : route('login') }}" class="btn-ghost btn-sm"><x-dashboard.icon name="user-round" class="size-4" /> {{ $user ? 'My account' : 'Sign in' }}</a>
        </div>
    </header>

    <main id="main" tabindex="-1" class="mx-auto max-w-3xl space-y-5 px-4 pt-8 sm:px-6">
        {{-- Confirmed --}}
        <section class="card overflow-hidden">
            <div class="relative bg-forest-900 px-6 py-8 text-center text-cream sm:px-10">
                <div class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true"></div>
                <svg viewBox="0 0 56 56" class="relative mx-auto size-16 text-sand-300" aria-hidden="true">
                    <circle class="tick-circle" cx="28" cy="28" r="24" fill="none" stroke="currentColor" stroke-width="3" />
                    <path class="tick-mark" d="M17 29l7 7 15-16" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h1 class="relative mt-3 font-display text-[28px] font-semibold tracking-tight">Order confirmed ✓</h1>
                <p class="relative mt-1 font-mono text-[14px] text-sand-200">Order #{{ $order['id'] }}</p>
                <p class="relative mt-3 text-[14px] text-cream/80">{{ $order['branch_full'] }} · Your order has been confirmed.</p>
                <p class="relative mt-1 text-[15px] font-semibold">Pickup: Today · <span class="tabular-nums" x-text="order.pickup_time"></span></p>
            </div>

            <div class="px-5 py-6 sm:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-[18px] font-semibold" x-text="headline()"></h2>
                        <p class="text-[13.5px] text-ink-soft" x-text="countdown()"></p>
                    </div>
                    <x-pickup.status-badge status="order.status" />
                </div>
                <x-pickup.status-timeline class="mt-6" />
            </div>
        </section>

        {{-- Pickup pass --}}
        <section class="card px-5 py-7 sm:px-8" aria-labelledby="pickup-heading" x-show="order.status !== 'cancelled'">
            <h2 id="pickup-heading" class="sr-only">Your pickup code and QR</h2>
            <div class="grid items-center gap-8 sm:grid-cols-2">
                <x-pickup.code-display />
                <x-pickup.qr-card />
            </div>
        </section>
        <section class="card border-danger/30 bg-danger-soft px-5 py-5 text-[14px] text-danger" x-show="order.status === 'cancelled'" x-cloak>
            This order was cancelled by the café. Any digital payment is refunded. Please call {{ $branch['phone'] ?? 'the café' }} if you have questions.
        </section>

        <div class="grid gap-5 sm:grid-cols-2">
            {{-- Branch --}}
            @if ($branch)
                <section class="card overflow-hidden">
                    <img src="{{ \App\Services\Demo\DemoCatalog::image($branch['image']) }}" alt="" class="h-32 w-full object-cover">
                    <div class="space-y-2.5 p-5 text-[13.5px]">
                        <p class="font-display text-[16px] font-semibold">{{ $branch['name'] }}</p>
                        <p class="flex items-start gap-2.5 text-ink-soft"><x-dashboard.icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-ink-faint" /> {{ $branch['area'] }}</p>
                        <p class="flex items-start gap-2.5 text-ink-soft"><x-dashboard.icon name="clock" class="mt-0.5 size-4 shrink-0 text-ink-faint" /> {{ $hours }}</p>
                        <p class="flex items-center gap-2.5"><x-dashboard.icon name="phone" class="size-4 text-ink-faint" /> <a href="tel:{{ str_replace(' ', '', $branch['phone']) }}" class="hover:underline">{{ $branch['phone'] }}</a></p>
                        <a href="{{ $branch['maps'] }}" target="_blank" rel="noopener" class="btn-secondary mt-2 w-full"><x-dashboard.icon name="navigation" class="size-4" /> Get directions</a>
                    </div>
                </section>
            @endif

            {{-- Summary --}}
            <section class="card p-5" x-data="{ open: true }">
                <button type="button" class="flex w-full items-center justify-between text-left" @click="open = !open" :aria-expanded="open" aria-controls="order-summary">
                    <span class="font-display text-[16px] font-semibold">Order summary</span>
                    <span x-html="$icon(open ? 'chevron-down' : 'chevron-right', 'size-4 text-ink-faint')"></span>
                </button>
                <div id="order-summary" x-show="open" x-transition.opacity class="mt-4">
                    <ul class="space-y-2.5 text-[13.5px]">
                        @foreach ($order['lines'] as $line)
                            <li class="flex items-baseline gap-2"><span class="font-semibold tabular-nums">{{ $line['qty'] }} ×</span><span class="min-w-0 flex-1">{{ $line['name'] }}</span><span class="tabular-nums text-ink-soft">{{ \App\Services\Dashboard\OrderPresenter::etb($line['subtotal']) }}</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex items-center justify-between border-t border-dashed border-line pt-3">
                        <span class="text-ink-soft">Total</span>
                        <span class="font-display text-[18px] font-semibold tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($order['total']) }}</span>
                    </div>
                    <p class="mt-2 text-[12.5px] text-ink-faint">Pay at pickup (demo) · {{ $order['customer'] }} · {{ $order['customer_phone'] }}</p>
                    @if ($order['notes'])
                        <p class="mt-3 rounded-lg bg-subtle px-3 py-2 text-[12.5px] text-ink-soft ring-1 ring-line"><span class="font-medium text-ink">Your request:</span> {{ $order['notes'] }}</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 text-[13px]">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 font-medium text-accent hover:underline"><x-dashboard.icon name="arrow-left" class="size-4" /> Back to KOBA</a>
            @if ($user)
                <a href="{{ route('dashboard') }}" class="btn-secondary">Follow it on my dashboard <x-dashboard.icon name="arrow-right" class="size-4" /></a>
            @else
                <p class="text-ink-faint">Keep this page open, or bookmark it, to show your code at the café.</p>
            @endif
        </div>
        <p class="text-center text-[11.5px] text-ink-faint">Demo order — not a real order or payment.</p>
    </main>

    <x-pickup.qr-fullscreen />
</div>
@endsection

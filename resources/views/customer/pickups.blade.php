@extends('layouts.customer')

@section('title', 'Pickups')

@section('content')
<x-dashboard.page-header eyebrow="Your KOBA" title="Pickups" description="Where and when to collect. Upcoming pickups update live as the kitchen works." />

<section class="mb-8" x-data="{ now: {{ $now }}, init() { setInterval(() => this.now++, 1000) } }">
    <h2 class="mb-3 font-display text-[16px] font-semibold">Upcoming</h2>
    @forelse ($upcoming as $o)
        <article class="card mb-3 flex flex-col overflow-hidden sm:flex-row">
            <div class="flex shrink-0 flex-col justify-center bg-forest-900 px-6 py-5 text-cream sm:w-48">
                <p class="text-[11px] font-semibold tracking-[0.14em] text-sand-400 uppercase">{{ $o['pickup_date'] }}</p>
                <p class="mt-1 font-display text-[32px] leading-none font-semibold tabular-nums">{{ $o['pickup_time'] }}</p>
                <p class="mt-1.5 text-xs text-cream/70" x-text="{{ $o['pickup_at'] }} > now ? 'in ' + Math.max(1, Math.round(({{ $o['pickup_at'] }} - now) / 60)) + ' min' : 'Ready when you are'"></p>
            </div>
            <div class="flex flex-1 flex-wrap items-center gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2"><span class="font-medium">{{ $o['branch_full'] }}</span><x-dashboard.status :value="$o['status']" /></p>
                    <p class="mt-0.5 text-[13px] text-ink-soft">{{ $o['items'] }}</p>
                    <p class="mt-1 text-xs text-ink-faint"><span class="font-mono">{{ $o['id'] }}</span> · {{ $o['branch_area'] }} · {{ $o['payment_label'] }}</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-order', @js($o['id']))">Details</button>
                    <a href="{{ $o['maps'] }}" target="_blank" rel="noopener" class="btn-primary btn-sm"><x-dashboard.icon name="navigation" class="size-3.5" /> Directions</a>
                </div>
            </div>
        </article>
    @empty
        <div class="card"><x-dashboard.empty-state icon="calendar-clock" title="No pickups coming up" body="When you pre-order, your pickup time and café appear here.">
            <a href="{{ route('menu') }}" class="btn-primary"><x-dashboard.icon name="shopping-bag" /> Browse the menu</a>
        </x-dashboard.empty-state></div>
    @endforelse
</section>

<section>
    <h2 class="mb-3 font-display text-[16px] font-semibold">Past pickups</h2>
    <div class="card overflow-hidden">
        <ol class="divide-y divide-line">
            @foreach ($past as $o)
                <li class="flex cursor-pointer items-center gap-4 px-5 py-3 hover:bg-subtle" @click="$dispatch('open-order', @js($o['id']))">
                    <div class="w-20 shrink-0"><p class="text-[13px] font-medium">{{ $o['pickup_date'] }}</p><p class="text-xs tabular-nums text-ink-faint">{{ $o['pickup_time'] }}</p></div>
                    <div class="min-w-0 flex-1"><p class="truncate text-[13px]">{{ $o['items'] }}</p><p class="text-xs text-ink-faint">{{ $o['branch_name'] }}</p></div>
                    <x-dashboard.status :value="$o['status']" />
                    <p class="hidden w-28 text-right text-[13px] font-medium tabular-nums sm:block">{{ \App\Services\Dashboard\OrderPresenter::etb($o['total']) }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endsection

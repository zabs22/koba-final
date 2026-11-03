@extends('layouts.branch')

@section('title', 'Pickup schedule')

@section('content')
@php
    $max = max(1, ...array_map(fn ($s) => count($s['orders']), $slots ?: [['orders' => []]]));
    $capacity = (int) $settings['max_per_slot'];
@endphp

<x-dashboard.page-header eyebrow="Service" title="Pickup schedule"
    :description="'Today in '.$settings['slot_minutes'].'-minute slots · up to '.$capacity.' pickups per slot. Busy slots are shaded darker.'">
    <x-slot:actions><a href="{{ route('branch.settings') }}" class="btn-ghost btn-sm"><x-dashboard.icon name="settings" class="size-3.5" /> Slot settings</a></x-slot:actions>
</x-dashboard.page-header>

@if (! $slots)
    <div class="card"><x-dashboard.empty-state icon="calendar-clock" title="No pickups today yet" body="Pre-orders appear here by pickup time as customers place them." /></div>
@else
    {{-- Day at a glance: one bar per slot --}}
    <x-dashboard.card title="Today at a glance" subtitle="Pickups per slot · click a slot to jump to it" class="mb-5">
        <div class="flex h-28 items-end gap-1 overflow-x-auto pb-1">
            @foreach ($slots as $slot)
                @php $n = count($slot['orders']); @endphp
                <a href="#slot-{{ $slot['start'] }}" class="group flex min-w-[18px] flex-1 flex-col items-center gap-1" title="{{ $slot['label'] }}: {{ $n }} pickups">
                    <span class="w-full rounded-t-[4px] transition-colors {{ $slot['current'] ? 'bg-sand-500' : ($slot['past'] ? 'bg-forest-200 dark:bg-forest-800' : 'bg-forest-500 group-hover:bg-forest-700') }} {{ $n > $capacity ? 'ring-2 ring-danger' : '' }}"
                          style="height: {{ max(6, $n / $max * 96) }}px"></span>
                    @if ($loop->index % 4 === 0)<span class="text-[10px] tabular-nums text-ink-faint">{{ substr($slot['label'], 0, 5) }}</span>@else<span class="text-[10px]">&nbsp;</span>@endif
                </a>
            @endforeach
        </div>
        <p class="mt-2 flex flex-wrap gap-4 text-xs text-ink-faint">
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-forest-200 dark:bg-forest-800"></span>Done</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-sand-500"></span>Now</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-forest-500"></span>Upcoming</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm ring-2 ring-danger"></span>Over capacity</span>
        </p>
    </x-dashboard.card>

    <ol class="space-y-3">
        @foreach ($slots as $slot)
            <li id="slot-{{ $slot['start'] }}" class="card scroll-mt-24 overflow-hidden {{ $slot['current'] ? 'ring-2 ring-sand-400' : '' }} {{ $slot['past'] ? 'opacity-70' : '' }}">
                <div class="flex flex-col sm:flex-row">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line bg-subtle px-4 py-3 sm:w-44 sm:flex-col sm:items-start sm:justify-center sm:border-r sm:border-b-0">
                        <div>
                            <p class="font-display text-[15px] font-semibold tabular-nums">{{ $slot['label'] }}</p>
                            <p class="text-xs text-ink-faint">{{ count($slot['orders']) }} {{ str('pickup')->plural(count($slot['orders'])) }} · {{ \App\Services\Dashboard\OrderPresenter::etb($slot['total'], 0) }}</p>
                        </div>
                        @if ($slot['current'])<span class="rounded-full bg-sand-400 px-2 py-0.5 text-[10.5px] font-bold text-forest-900 uppercase">Now</span>@endif
                    </div>
                    <ul class="flex-1 divide-y divide-line">
                        @foreach ($slot['orders'] as $o)
                            <li class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-subtle" x-data @click="$dispatch('open-order', @js($o['id']))">
                                <span class="w-12 font-display text-[13px] font-semibold tabular-nums">{{ $o['pickup_time'] }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-medium">{{ $o['customer'] }} <span class="font-mono text-[11.5px] font-normal text-ink-faint">{{ $o['id'] }}</span></span>
                                    <span class="block truncate text-xs text-ink-soft">{{ $o['items'] }}</span>
                                </span>
                                <span class="hidden text-[13px] tabular-nums sm:block">{{ \App\Services\Dashboard\OrderPresenter::etb($o['total']) }}</span>
                                <x-dashboard.status :value="$o['status']" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
        @endforeach
    </ol>
@endif
@endsection

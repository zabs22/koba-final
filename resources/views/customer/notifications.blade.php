@extends('layouts.customer')

@section('title', 'Notifications')

@section('content')
@php
    $tones = [
        'danger' => 'bg-danger-soft text-danger',
        'success' => 'bg-success-soft text-success',
        'sand' => 'bg-sand-100 text-brown-600 dark:bg-sand-400/15 dark:text-sand-300',
        'muted' => 'bg-subtle text-ink-faint',
    ];
@endphp

<div x-data="{ unread: {{ count(array_filter($items, fn ($n) => $n['unread'])) }} }">
    <x-dashboard.page-header eyebrow="Your KOBA" title="Notifications" description="Order updates from the kitchen, and the occasional treat.">
        <x-slot:actions>
            <button type="button" class="btn-secondary" x-show="unread" @click="$action(@js(route('customer.notifications.read')), {}, 'POST').then(r => { if (r) { unread = 0; document.querySelectorAll('[data-unread]').forEach(el => el.remove()) } })">
                <x-dashboard.icon name="check" /> Mark all as read
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <div class="card overflow-hidden">
        <ul class="divide-y divide-line">
            @forelse ($items as $n)
                <li>
                    <a href="{{ $n['href'] }}" class="flex gap-4 px-5 py-4 hover:bg-subtle">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tones[$n['tone']] ?? $tones['muted'] }}"><x-dashboard.icon :name="$n['icon']" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-3">
                                <span class="text-[14px] font-medium">{{ $n['title'] }}</span>
                                <span class="flex shrink-0 items-center gap-2 text-xs text-ink-faint">
                                    {{ $n['when'] }}
                                    @if ($n['unread'])<span data-unread class="size-2 rounded-full bg-sand-500" aria-label="Unread"></span>@endif
                                </span>
                            </span>
                            <span class="mt-0.5 block text-[13px] text-ink-soft">{{ $n['body'] }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li><x-dashboard.empty-state icon="bell" title="You're all caught up" body="Order updates will appear here." /></li>
            @endforelse
        </ul>
    </div>
</div>
@endsection

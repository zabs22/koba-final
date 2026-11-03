@props(['value', 'label' => null])

{{-- Server-rendered status badge (same look as the JS badge in resources/js/dashboard/cells.js). --}}
@php
    $styles = [
        'pending' => ['New', 'bg-sand-100 text-brown-700 dark:bg-sand-400/15 dark:text-sand-300', 'bg-sand-500'],
        'preparing' => ['Preparing', 'bg-info-soft text-info', 'bg-info'],
        'ready' => ['Ready', 'bg-success-soft text-success', 'bg-success'],
        'completed' => ['Picked up', 'bg-subtle text-ink-soft ring-1 ring-inset ring-line', null],
        'cancelled' => ['Cancelled', 'bg-danger-soft text-danger', 'bg-danger'],
        'open' => ['Open', 'bg-success-soft text-success', 'bg-success'],
        'paused' => ['Paused', 'bg-warning-soft text-warning', 'bg-warning'],
        'closed' => ['Closed', 'bg-subtle text-ink-soft ring-1 ring-inset ring-line', 'bg-ink-faint'],
    ];
    [$text, $class, $dot] = $styles[$value] ?? [ucfirst($value), 'bg-subtle text-ink-soft ring-1 ring-inset ring-line', null];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium $class"]) }}>
    @if ($dot)<span class="size-1.5 rounded-full {{ $dot }}" aria-hidden="true"></span>@endif
    {{ $label ?? $text }}
</span>

{{-- Order status for pickup screens. `status` is an Alpine expression. Ready pulses green. --}}
@props(['status' => 'pass.status'])

<span {{ $attributes->class('inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.08em] uppercase') }}
      :class="{
        'bg-sand-100 text-brown-600 dark:bg-sand-400/15 dark:text-sand-300': {{ $status }} === 'pending',
        'bg-warning-soft text-warning': {{ $status }} === 'preparing',
        'bg-success-soft text-success': {{ $status }} === 'ready',
        'bg-ink/8 text-ink-soft': {{ $status }} === 'completed',
        'bg-danger-soft text-danger': {{ $status }} === 'cancelled',
      }">
    <span x-show="{{ $status }} === 'ready'" class="live-dot"></span>
    <span x-show="{{ $status }} !== 'ready'" class="size-1.5 rounded-full bg-current"></span>
    <span x-text="{ pending: 'Order received', preparing: 'Preparing', ready: 'Ready for pickup', completed: 'Picked up', cancelled: 'Cancelled' }[{{ $status }}]"></span>
</span>

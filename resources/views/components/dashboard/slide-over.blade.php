@props(['show' => 'open', 'close' => 'open = false', 'width' => 'max-w-lg', 'title' => null])

{{-- Right-hand panel. `show` / `close` are Alpine expressions of the enclosing component. --}}
<div x-show="{{ $show }}" x-cloak class="fixed inset-0 z-[60]" @keydown.escape.window="{{ $close }}" role="dialog" aria-modal="true" {{ $attributes }}>
    <div x-show="{{ $show }}" x-transition.opacity class="absolute inset-0 bg-forest-950/40" @click="{{ $close }}"></div>
    <div x-show="{{ $show }}" x-trap.noscroll="{{ $show }}"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="absolute inset-y-0 right-0 flex w-full {{ $width }} flex-col border-l border-line bg-panel shadow-[var(--shadow-pop)]">
        @if ($title)
            <header class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-display text-base font-semibold">{{ $title }}</h2>
                <button type="button" class="btn-icon -mr-2" @click="{{ $close }}" aria-label="Close"><x-dashboard.icon name="x" /></button>
            </header>
        @endif
        {{ $slot }}
    </div>
</div>

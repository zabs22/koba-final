@props(['items' => [], 'all' => null, 'readUrl' => null])

@php
    $tones = [
        'danger' => 'bg-danger-soft text-danger',
        'warning' => 'bg-warning-soft text-warning',
        'success' => 'bg-success-soft text-success',
        'sand' => 'bg-sand-100 text-brown-600 dark:bg-sand-400/15 dark:text-sand-300',
        'info' => 'bg-info-soft text-info',
        'muted' => 'bg-subtle text-ink-faint',
    ];
    $unread = count(array_filter($items, fn ($n) => $n['unread'] ?? true));
@endphp

<div x-data="{ open: false, unread: {{ $unread }} }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" class="btn-icon relative" @click="open = !open" :aria-expanded="open" aria-haspopup="true" aria-label="Notifications">
        <x-dashboard.icon name="bell" class="size-[18px]" />
        <span x-show="unread" class="absolute top-1.5 right-1.5 grid min-w-4 place-items-center rounded-full bg-danger px-1 text-[10px] font-semibold text-white ring-2 ring-canvas dark:text-forest-950" x-text="unread"></span>
    </button>

    <div x-show="open" x-transition.origin.top.right x-cloak
         class="absolute right-0 z-40 mt-2 w-[min(380px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-line bg-panel shadow-[var(--shadow-pop)]">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <p class="font-display text-sm font-semibold">Notifications</p>
            @if ($readUrl)
                <button type="button" class="text-xs font-medium text-accent hover:underline" x-show="unread" @click="$action('{{ $readUrl }}', {}, 'POST').then(() => unread = 0)">Mark all as read</button>
            @endif
        </div>
        <ul class="max-h-[420px] divide-y divide-line overflow-y-auto">
            @forelse ($items as $n)
                <li>
                    <a href="{{ $n['href'] ?? '#' }}" class="flex gap-3 px-4 py-3 hover:bg-subtle">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $tones[$n['tone']] ?? $tones['muted'] }}"><x-dashboard.icon :name="$n['icon']" class="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-[13px] font-medium text-ink">{{ $n['title'] }}</span>
                                <span class="shrink-0 text-[11px] text-ink-faint">{{ $n['when'] }}</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-ink-soft">{{ $n['body'] }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-[13px] text-ink-faint">You're all caught up.</li>
            @endforelse
        </ul>
        @if ($all)
            <a href="{{ $all }}" class="block border-t border-line px-4 py-2.5 text-center text-[13px] font-medium text-accent hover:bg-subtle">View all</a>
        @endif
    </div>
</div>

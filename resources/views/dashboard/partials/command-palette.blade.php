{{-- Command palette (Ctrl/⌘ + K): pages for this role + server-side order search. --}}
@php
    $user = auth()->user();
    $config = ['items' => \App\Support\Dashboard\Navigation::palette($user), 'searchUrl' => route($user->role()->area().'.search')];
@endphp

<div x-data="commandPalette(@js($config))" @open-palette.window="show()" x-show="open" x-cloak class="fixed inset-0 z-[85]" role="dialog" aria-modal="true" aria-label="Command palette">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-forest-950/45" @click="close()"></div>
    <div x-show="open" x-trap.noscroll="open" x-transition.scale.98.origin.top
         class="relative mx-auto mt-[12vh] w-[min(640px,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-panel shadow-[var(--shadow-pop)]"
         @keydown.escape.prevent="close()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()">
        <div class="flex items-center gap-3 border-b border-line px-4">
            <x-dashboard.icon name="search" class="size-[18px] text-ink-faint" />
            <input x-ref="input" x-model="q" type="text" placeholder="Jump to a page, or search orders by number or name…" aria-label="Search"
                   class="h-14 flex-1 border-0 bg-transparent px-0 text-[15px] text-ink placeholder:text-ink-faint focus:ring-0">
            <span x-show="searching" class="size-4 animate-spin" x-html="$icon('loader-circle', 'size-4 text-ink-faint')"></span>
            <kbd class="rounded border border-line bg-subtle px-1.5 text-[11px] text-ink-faint">Esc</kbd>
        </div>
        <ul x-ref="list" class="max-h-[min(60vh,440px)] overflow-y-auto p-2" role="listbox">
            <template x-for="(item, i) in results" :key="item.label + i">
                <li>
                    <p x-show="i === 0 || results[i - 1].group !== item.group" class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-wider text-ink-faint uppercase" x-text="item.group"></p>
                    <button type="button" role="option" :aria-selected="active === i" @mouseenter="active = i" @click="choose(item)"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left" :class="active === i ? 'bg-accent-soft' : ''">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-subtle text-ink-soft ring-1 ring-line" x-html="$icon(item.icon, 'size-4')"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13.5px] font-medium text-ink" x-text="item.label"></span>
                            <span class="block truncate text-xs text-ink-faint" x-show="item.sub" x-text="item.sub"></span>
                        </span>
                        <span x-show="active === i" x-html="$icon('arrow-right', 'size-4 text-ink-faint')"></span>
                    </button>
                </li>
            </template>
            <li x-show="!results.length && !searching" class="px-3 py-10 text-center text-[13px] text-ink-faint">No matches for “<span x-text="q"></span>”.</li>
        </ul>
        <div class="flex items-center gap-4 border-t border-line bg-subtle px-4 py-2 text-[11.5px] text-ink-faint">
            <span><kbd class="font-sans">↑↓</kbd> to move</span><span><kbd class="font-sans">Enter</kbd> to open</span><span class="ml-auto">Ctrl / ⌘ K</span>
        </div>
    </div>
</div>

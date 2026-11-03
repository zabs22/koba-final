{{-- Confirmation modal: $dispatch('confirm', { title, body, confirm, danger, then }) --}}
<div x-data="confirmModal" @confirm.window="ask($event.detail)" x-show="open" x-cloak class="fixed inset-0 z-[90] grid place-items-center p-4" role="alertdialog" aria-modal="true" :aria-label="title" @keydown.escape.window="open = false">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-forest-950/50" @click="open = false"></div>
    <div x-show="open" x-trap="open" x-transition.scale.95 class="relative w-full max-w-md rounded-2xl border border-line bg-panel p-6 shadow-[var(--shadow-pop)]">
        <div class="flex gap-4">
            <span class="grid size-10 shrink-0 place-items-center rounded-full" :class="danger ? 'bg-danger-soft text-danger' : 'bg-accent-soft text-accent'"
                  x-html="$icon(danger ? 'triangle-alert' : 'circle-alert', 'size-5')"></span>
            <div>
                <h2 class="font-display text-base font-semibold" x-text="title"></h2>
                <p class="mt-1 text-[13.5px] text-ink-soft" x-text="body"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn-secondary" @click="open = false">Cancel</button>
            <button type="button" x-ref="confirm" :class="danger ? 'btn-danger' : 'btn-primary'" @click="accept()" x-text="confirmLabel"></button>
        </div>
    </div>
</div>

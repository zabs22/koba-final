{{-- Toasts: $dispatch('toast', { type: 'success'|'error'|'info'|'order', message, detail?, link? }) --}}
<div x-data="toasts" @toast.window="push($event.detail)" class="pointer-events-none fixed inset-x-0 bottom-0 z-[80] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6" aria-live="polite" role="status">
    <template x-for="toast in items" :key="toast.id">
        <div x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-line bg-panel p-3.5 shadow-[var(--shadow-pop)]">
            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full"
                  :class="{ 'bg-success-soft text-success': toast.type === 'success', 'bg-danger-soft text-danger': toast.type === 'error', 'bg-sand-100 text-brown-600 dark:bg-sand-400/15 dark:text-sand-300': toast.type === 'order', 'bg-info-soft text-info': toast.type === 'info' }"
                  x-html="$icon({ success: 'circle-check', error: 'circle-alert', order: 'bell-ring', info: 'info' }[toast.type] || 'info', 'size-4')"></span>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-medium text-ink" x-text="toast.message"></p>
                <p class="mt-0.5 truncate text-xs text-ink-soft" x-show="toast.detail" x-text="toast.detail"></p>
                <a x-show="toast.link" :href="toast.link?.href" class="mt-1 inline-block text-xs font-medium text-accent hover:underline" x-text="toast.link?.label"></a>
            </div>
            <button type="button" class="-m-1 grid size-7 place-items-center rounded-md text-ink-faint hover:bg-subtle hover:text-ink" @click="dismiss(toast.id)" aria-label="Dismiss"><span x-html="$icon('x', 'size-3.5')"></span></button>
        </div>
    </template>
</div>

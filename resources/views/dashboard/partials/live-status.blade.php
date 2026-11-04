{{-- "Live · updated 5s ago" strip for pages built on the `page` component. --}}
<div class="-mt-2 mb-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-faint">
    <span class="inline-flex items-center gap-2" x-show="payload.range?.live !== false">
        <span class="live-dot" :class="failed && '!bg-danger'"></span>
        <span x-text="failed ? 'Connection lost — retrying' : 'Live'"></span>
    </span>
    <span x-text="updatedLabel()"></span>
    <button type="button" class="inline-flex items-center gap-1 hover:text-ink" @click="refresh()" :disabled="loading">
        <span x-html="$icon('refresh-cw', 'size-3' + (loading ? ' animate-spin' : ''))"></span> Refresh
    </button>
</div>

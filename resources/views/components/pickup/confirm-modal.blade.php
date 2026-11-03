{{-- "Confirm pickup?" (pickupVerification scope). Focus is trapped; Escape cancels and returns focus to Confirm pickup. --}}
<div x-show="modal" x-cloak class="fixed inset-0 z-[90] grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-pickup-title" aria-describedby="confirm-pickup-body"
     @keydown.escape.prevent.stop="closeModal()">
    <div x-show="modal" x-transition.opacity class="absolute inset-0 bg-forest-950/55" @click="closeModal()"></div>
    <div x-show="modal" x-trap.noscroll="modal" x-transition.scale.95 class="relative w-full max-w-md rounded-2xl border border-line bg-panel p-6 shadow-[var(--shadow-pop)]">
        <template x-if="result?.order">
            <div>
                <div class="flex items-start gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-accent-soft text-accent"><x-dashboard.icon name="hand-platter" class="size-5" /></span>
                    <div>
                        <h2 id="confirm-pickup-title" class="font-display text-lg font-semibold">Confirm pickup?</h2>
                        <p class="mt-0.5 font-mono text-[13px] text-ink-soft" x-text="'Order #' + result.order.id"></p>
                    </div>
                </div>
                <dl class="mt-5 rounded-xl bg-subtle px-4 py-3 text-[13.5px] ring-1 ring-line">
                    <dt class="text-xs text-ink-faint">Customer</dt>
                    <dd class="mt-0.5 font-semibold" x-text="result.order.customer"></dd>
                </dl>
                <p id="confirm-pickup-body" class="mt-4 text-[14px] text-ink-soft">Are you sure this order has been handed to the customer?</p>
                <p x-show="checked.length < result.order.lines.length" class="mt-2 flex items-center gap-1.5 text-[12.5px] text-warning">
                    <x-dashboard.icon name="list-checks" class="size-4" />
                    <span><span x-text="checked.length"></span> of <span x-text="result.order.lines.length"></span> items checked — that’s fine if you’ve checked them by eye.</span>
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary h-11" @click="closeModal()" :disabled="saving">Cancel</button>
                    <button type="button" x-ref="modalConfirm" class="btn-primary h-11 min-w-[160px]" @click="confirm()" :disabled="saving">
                        <span x-html="$icon(saving ? 'loader-circle' : 'circle-check', 'size-4' + (saving ? ' animate-spin' : ''))"></span>
                        <span x-text="saving ? 'Saving…' : 'Confirm pickup'"></span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

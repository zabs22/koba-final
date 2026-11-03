{{-- What the lookup found (pickupVerification scope). Finding an order never completes it. --}}
<section {{ $attributes->class('card relative min-h-[420px] overflow-hidden') }} aria-label="Pickup result">

    {{-- Waiting for the next customer --}}
    <div x-show="state === 'idle'" class="flex h-full min-h-[420px] flex-col items-center justify-center px-6 py-12 text-center">
        <span class="grid size-16 place-items-center rounded-2xl bg-accent-soft text-accent"><x-dashboard.icon name="hand-platter" class="size-8" /></span>
        <h2 class="mt-4 font-display text-lg font-semibold">Ready for the next customer</h2>
        <p class="mt-1 max-w-sm text-[13.5px] text-ink-soft">Scan their QR code or type the KB code. You’ll see the order here and can check it before handing it over.</p>
        <ol class="mt-6 grid w-full max-w-md gap-2 text-left text-[13px] sm:grid-cols-3">
            @foreach ([['scan-qr-code', 'Scan or type'], ['list-checks', 'Check the order'], ['circle-check', 'Confirm pickup']] as $i => [$icon, $step])
                <li class="flex items-center gap-2 rounded-lg bg-subtle px-3 py-2 ring-1 ring-line"><span class="font-display font-semibold text-brown-600 dark:text-sand-400">{{ $i + 1 }}</span><x-dashboard.icon :name="$icon" class="size-4 text-ink-faint" /> {{ $step }}</li>
            @endforeach
        </ol>
    </div>

    {{-- Scanning / searching --}}
    <div x-show="state === 'scanning' || state === 'searching'" x-cloak class="flex min-h-[420px] flex-col items-center justify-center gap-3 p-8 text-center">
        <x-dashboard.icon name="loader-circle" class="size-8 animate-spin text-accent" />
        <p class="font-medium" x-text="state === 'scanning' ? 'Waiting for a QR code…' : 'Searching KB-' + input + '…'"></p>
        <div class="mt-4 w-full max-w-md space-y-3" x-show="state === 'searching'"><div class="skeleton h-10"></div><div class="skeleton h-24"></div><div class="skeleton h-12"></div></div>
    </div>

    {{-- Not found / not a code --}}
    <div x-show="state === 'not_found' || state === 'invalid'" x-cloak class="flex min-h-[420px] flex-col items-center justify-center px-6 py-12 text-center">
        <span class="grid size-16 place-items-center rounded-full bg-danger-soft text-danger"><x-dashboard.icon name="circle-x" class="size-8" /></span>
        <h2 class="mt-4 font-display text-xl font-semibold">Pickup code not found</h2>
        <p class="mt-1 text-[14px] text-ink-soft">Please check the code and try again.</p>
        <p class="mt-1 font-mono text-[13px] text-ink-faint" x-show="result?.code" x-text="result?.code"></p>
        <p class="mt-4 max-w-sm rounded-lg bg-subtle px-3 py-2 text-[12.5px] text-ink-soft ring-1 ring-line">Codes never contain O, 0, I, 1 or L, so they can’t be misread. Ask the customer to show the QR if unsure.</p>
        <button type="button" class="btn-primary mt-5 h-11" @click="tryAgain()"><x-dashboard.icon name="rotate-ccw" class="size-4" /> Try again</button>
    </div>

    {{-- Another branch's code --}}
    <div x-show="state === 'wrong_branch'" x-cloak class="flex min-h-[420px] flex-col items-center justify-center px-6 py-12 text-center">
        <span class="grid size-16 place-items-center rounded-full bg-warning-soft text-warning"><x-dashboard.icon name="store" class="size-8" /></span>
        <h2 class="mt-4 font-display text-xl font-semibold">This order belongs to another branch</h2>
        <p class="mt-1 max-w-sm text-[14px] text-ink-soft"><span class="font-mono" x-text="result?.code"></span> is for <strong class="font-semibold text-ink" x-text="result?.branch"></strong>. Ask the customer to collect it there.</p>
        <button type="button" class="btn-secondary mt-5 h-11" @click="tryAgain()"><x-dashboard.icon name="rotate-ccw" class="size-4" /> Next customer</button>
    </div>

    {{-- An order: found, not ready, already picked up, cancelled --}}
    <template x-if="result?.order && ['found', 'not_ready', 'already_picked_up', 'cancelled'].includes(state)">
        <div x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
            <header class="flex items-center gap-3 px-5 py-3.5 text-[13px] font-semibold tracking-[0.1em] uppercase"
                    :class="{ 'bg-success text-white dark:text-forest-950': state === 'found', 'bg-warning-soft text-warning': state === 'not_ready', 'bg-ink/8 text-ink': state === 'already_picked_up', 'bg-danger text-white dark:text-forest-950': state === 'cancelled' }">
                <span x-html="$icon({ found: 'circle-check', not_ready: 'hourglass', already_picked_up: 'triangle-alert', cancelled: 'ban' }[state], 'size-5')"></span>
                <span x-text="{ found: 'Order found', not_ready: 'Order not ready yet', already_picked_up: 'Order already picked up', cancelled: 'This order was cancelled' }[state]"></span>
                <span class="ml-auto font-mono text-[12px] tracking-[0.12em] normal-case opacity-80" x-text="result.code"></span>
            </header>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-ink-faint">Order</p>
                        <button type="button" class="font-mono text-[17px] font-semibold tracking-tight hover:underline" @click="$dispatch('open-order', result.order.id)" x-text="'#' + result.order.id"></button>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-html="$avatar(result.order.initials, result.order.customer_id)"></span>
                        <div class="min-w-0">
                            <p class="truncate text-[16px] font-semibold" x-text="result.order.customer"></p>
                            <p class="text-[12.5px] text-ink-soft" x-text="result.order.customer_phone"></p>
                        </div>
                    </div>
                    <p class="flex items-center gap-2 rounded-lg bg-subtle px-3 py-2 text-[12.5px] text-ink-soft ring-1 ring-line" x-show="state === 'found'">
                        <x-dashboard.icon name="user-check" class="size-4 shrink-0 text-accent" /> Ask the customer to confirm their name.
                    </p>
                    <div class="grid grid-cols-2 gap-3 text-[13px]">
                        <div>
                            <p class="text-xs text-ink-faint">Pickup time</p>
                            <p class="mt-0.5 font-display text-[20px] font-semibold tabular-nums" x-text="result.order.pickup_time"></p>
                            <span class="mt-1 inline-block rounded-md px-1.5 py-0.5 text-[11.5px] font-semibold"
                                  :class="{ 'bg-success-soft text-success': result.order.timing.key === 'on_time', 'bg-info-soft text-info': result.order.timing.key === 'early', 'bg-warning-soft text-warning': result.order.timing.key === 'late' }"
                                  x-text="result.order.timing.label" x-show="state === 'found'"></span>
                        </div>
                        <div>
                            <p class="text-xs text-ink-faint">Status</p>
                            <x-pickup.status-badge status="result.order.status" class="mt-1" />
                            <p class="mt-1.5 inline-flex items-center gap-1.5 rounded-md bg-subtle px-1.5 py-0.5 text-[11.5px] text-ink-soft ring-1 ring-line" x-text="result.order.payment_note"></p>
                        </div>
                    </div>
                    <div x-show="result.order.notes" class="rounded-lg border border-warning/40 bg-warning-soft px-3 py-2.5 text-[13px] text-warning">
                        <p class="flex items-center gap-1.5 text-[11.5px] font-semibold tracking-[0.08em] uppercase"><x-dashboard.icon name="message-square-text" class="size-3.5" /> Special instructions</p>
                        <p class="mt-1 font-medium" x-text="result.order.notes"></p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-ink-faint">Items</p>
                        <p class="text-[11.5px] text-ink-faint" x-show="state === 'found'"><span x-text="checked.length"></span> of <span x-text="result.order.lines.length"></span> checked · optional</p>
                    </div>
                    <ul class="mt-2 space-y-1.5">
                        <template x-for="(line, i) in result.order.lines" :key="i">
                            <li>
                                <button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left ring-1 transition-colors"
                                        :class="checked.includes(i) ? 'bg-success-soft ring-success/30' : 'ring-line hover:bg-subtle'"
                                        :disabled="state !== 'found'" @click="toggleLine(i)" :aria-pressed="checked.includes(i)">
                                    <span x-show="state === 'found'" x-html="$icon(checked.includes(i) ? 'square-check' : 'square', 'size-5 shrink-0 ' + (checked.includes(i) ? 'text-success' : 'text-ink-faint'))"></span>
                                    <span class="font-display text-[15px] font-semibold tabular-nums" x-text="line.qty + ' ×'"></span>
                                    <span class="min-w-0 flex-1 truncate text-[14px]" x-text="line.name"></span>
                                    <span class="text-[12.5px] tabular-nums text-ink-soft" x-text="$fmt(line.subtotal, 'etb')"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <div class="mt-3 flex items-center justify-between border-t border-dashed border-line pt-3">
                        <p class="text-[13px] text-ink-soft">Total</p>
                        <p class="font-display text-[20px] font-semibold tabular-nums" x-text="$fmt(result.order.total, 'etb')"></p>
                    </div>
                </div>
            </div>

            {{-- Still in the kitchen --}}
            <div x-show="state === 'not_ready'" class="mx-5 mb-5 rounded-xl bg-warning-soft/60 p-4 ring-1 ring-warning/25">
                <ol class="grid grid-cols-4 gap-2">
                    <template x-for="step in result.order.timeline" :key="step.status">
                        <li><span class="block h-1.5 rounded-full" :class="step.done ? 'bg-warning' : 'bg-ink/10'"></span><span class="mt-1.5 block text-[11.5px] font-medium" :class="step.done ? 'text-ink' : 'text-ink-faint'" x-text="step.label"></span></li>
                    </template>
                </ol>
                <p class="mt-3 text-[13px] text-ink-soft">The kitchen is still on this order<span x-show="result.order.eta"> — ready around <strong class="text-ink" x-text="result.order.eta?.ready_time"></strong></span>. Pickup can be confirmed once it is Ready.</p>
            </div>

            {{-- Already collected: possible code reuse --}}
            <div x-show="state === 'already_picked_up'" class="mx-5 mb-5 rounded-xl border border-warning/40 bg-warning-soft p-4 text-[13px] text-warning">
                <p class="font-semibold">Pickup completed at <span class="tabular-nums" x-text="result.order.picked_up?.time"></span></p>
                <p class="mt-0.5">Confirmed by <span x-text="result.order.picked_up?.by"></span><span x-show="result.order.picked_up?.via && result.order.picked_up.via !== 'board'" x-text="' · via ' + ({ qr: 'QR scan', code: 'typed code', list: 'ready list' }[result.order.picked_up?.via] ?? '')"></span>.</p>
                <p class="mt-2 text-ink-soft">If someone is showing this code again, it may have been shared. Don’t hand the order over twice.</p>
            </div>

            <div x-show="state === 'cancelled'" class="mx-5 mb-5 rounded-xl bg-danger-soft p-4 text-[13px] text-danger">This order was cancelled and must not be handed over. Any digital payment has been refunded.</div>

            <footer class="flex flex-wrap items-center gap-3 border-t border-line px-5 py-4">
                <button type="button" x-ref="confirmButton" x-show="state === 'found'" @click="askConfirm()"
                        class="btn-primary h-14 min-w-[240px] flex-1 text-[16px] tracking-wide uppercase">
                    <x-dashboard.icon name="circle-check" class="size-5" /> Confirm pickup
                </button>
                <button type="button" x-show="state === 'not_ready'" disabled class="btn-primary h-14 min-w-[240px] flex-1 text-[16px] tracking-wide uppercase">Confirm pickup</button>
                <button type="button" class="btn-secondary h-14" @click="reset()"><x-dashboard.icon name="rotate-ccw" class="size-4" /> <span x-text="state === 'found' ? 'Clear' : 'Next customer'"></span></button>
            </footer>
        </div>
    </template>

    {{-- Done --}}
    <template x-if="state === 'completed' && result?.order">
        <div class="flex min-h-[420px] flex-col items-center justify-center px-6 py-10 text-center">
            <svg viewBox="0 0 56 56" class="size-20 text-success" aria-hidden="true">
                <circle class="tick-circle" cx="28" cy="28" r="24" fill="none" stroke="currentColor" stroke-width="3" />
                <path class="tick-mark" d="M17 29l7 7 15-16" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-4 text-[13px] font-semibold tracking-[0.14em] text-success uppercase">✓ Pickup completed</p>
            <h2 class="mt-1 font-mono text-[18px] font-semibold" x-text="'#' + result.order.id"></h2>
            <p class="mt-2 text-[15px]"><strong x-text="result.order.customer"></strong> has received the order.</p>
            <p class="mt-0.5 text-[13.5px] text-ink-soft">Pickup completed at <span class="tabular-nums" x-text="completedAt"></span>.</p>
            <x-pickup.status-badge status="result.order.status" class="mt-3" />

            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <button type="button" class="btn-primary h-12" @click="reset()"><x-dashboard.icon name="scan-qr-code" class="size-5" /> Back to pickup verification</button>
                <button type="button" class="btn-secondary h-12" @click="$dispatch('open-order', result.order.id)"><x-dashboard.icon name="receipt-text" class="size-4" /> View order</button>
                <button type="button" class="btn-ghost h-12 text-danger" x-show="undoLeft > 0" @click="undo()"><x-dashboard.icon name="undo-2" class="size-4" /> Undo (<span class="tabular-nums" x-text="undoLeft"></span>s)</button>
            </div>

            <div x-show="offeringReturn" x-transition class="mt-6 flex items-center gap-3 rounded-full bg-subtle px-4 py-2 text-[13px] text-ink-soft ring-1 ring-line">
                <span>Back to verification in <span class="tabular-nums font-semibold text-ink" x-text="returnLeft"></span> s</span>
                <button type="button" class="font-medium text-accent hover:underline" @click="stay = true">Stay here</button>
            </div>
        </div>
    </template>
</section>

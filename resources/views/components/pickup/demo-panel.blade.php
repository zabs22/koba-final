{{-- Demo controls for presenting the pickup flow (pickupVerification scope). Local/testing only; a dropdown in the page header, so it never covers the counter. --}}
<div class="relative" @click.outside="demoOpen = false" @keydown.escape.stop="demoOpen = false">
    <button type="button" class="btn-secondary border-sand-400/60" @click="demoOpen = !demoOpen" :aria-expanded="demoOpen" aria-controls="pickup-demo-panel">
        <x-dashboard.icon name="sparkles" class="size-4 text-brown-600 dark:text-sand-400" /> Demo
        <span class="rounded bg-sand-100 px-1.5 text-[10px] font-bold tracking-wide text-brown-600 uppercase dark:bg-sand-400/15 dark:text-sand-300">Demo data</span>
    </button>
    <div id="pickup-demo-panel" x-show="demoOpen" x-cloak x-transition.opacity.origin.top.right role="region" aria-label="Demo controls"
         class="absolute top-full right-0 z-40 mt-2 w-[min(330px,calc(100vw-2rem))] space-y-3 rounded-2xl border border-sand-400/50 bg-panel p-4 shadow-[var(--shadow-pop)]">
        <p class="text-[11.5px] font-semibold tracking-wide text-danger uppercase">Demo data — not real orders or payments</p>
        <div>
            <label for="demo-scenario" class="label !mb-1 text-xs">Next demo scan returns</label>
            <select id="demo-scenario" x-model="scenario" class="field h-9 py-0 text-[13px]">
                <template x-for="s in feed.scenarios" :key="s.key">
                    <option :value="s.key" :disabled="!s.code" x-text="s.label + (s.code ? ' · ' + s.code : ' (none today)')"></option>
                </template>
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <button type="button" class="btn-primary btn-sm" @click="demoOpen = false; simulate()" :disabled="busy || !scenarioCode()"><x-dashboard.icon name="scan-qr-code" class="size-3.5" /> Demo scan</button>
            <button type="button" class="btn-secondary btn-sm" @click="demoReset()"><x-dashboard.icon name="rotate-ccw" class="size-3.5" /> Reset demo</button>
            <a href="{{ route('customer.dashboard') }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-dashboard.icon name="external-link" class="size-3.5" /> Customer view</a>
            <a href="{{ route('branch.verify') }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-dashboard.icon name="external-link" class="size-3.5" /> Manager view</a>
        </div>
        <p class="text-[11.5px] leading-relaxed text-ink-faint">One browser keeps one login: open the customer view in a private window or another browser and sign in as customer@test.com. Pickups confirmed here reach the customer within {{ config('dashboard.poll_seconds') }} s.</p>
    </div>
</div>

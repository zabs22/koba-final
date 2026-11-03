{{-- The pickup QR (always black on white, so it scans in dark mode too). Needs `pass` in scope. --}}
<div {{ $attributes->class('flex flex-col items-center text-center') }}>
    <p class="eyebrow">Your pickup QR</p>
    <button type="button" @click="$dispatch('open-pickup-qr', pass)" aria-label="Show the QR code full screen"
            class="mt-3 w-[min(232px,70vw)] rounded-2xl bg-white p-3 shadow-[var(--shadow-card)] ring-1 ring-line transition hover:ring-accent">
        <span class="block aspect-square [&_svg]:size-full" x-html="pass.qr"></span>
        <span class="mt-1.5 block font-mono text-[13px] font-semibold tracking-[0.14em] text-[#062e22]" x-text="pass.pickup_code"></span>
    </button>
    <p class="mt-3 max-w-[16rem] text-[12.5px] text-ink-soft">Show this QR code to café staff when collecting your order.</p>
    <button type="button" class="btn-primary mt-3" @click="$dispatch('open-pickup-qr', pass)"><x-dashboard.icon name="maximize-2" class="size-4" /> Show QR full screen</button>
</div>

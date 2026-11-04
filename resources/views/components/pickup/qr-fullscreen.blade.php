{{-- Full-screen QR for the counter: $dispatch('open-pickup-qr', pass). White in every theme; X, Escape or swipe down closes. --}}
<div x-data="pickupFullscreen" @open-pickup-qr.window="show($event.detail)" x-show="open" x-cloak
     class="fixed inset-0 z-[95] flex flex-col bg-white text-[#062e22]" role="dialog" aria-modal="true" aria-labelledby="pickup-qr-title"
     x-trap.noscroll.inert="open" @keydown.escape.prevent="close()"
     x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
     @touchstart.passive="touchStart($event)" @touchmove.passive="touchMove($event)" @touchend="touchEnd()"
     :style="pull ? `transform: translateY(${Math.min(pull, 200)}px); opacity: ${1 - Math.min(pull, 200) / 400}` : ''">
    <div class="mx-auto mt-2 h-1.5 w-12 rounded-full bg-[#062e22]/15 sm:hidden" aria-hidden="true"></div>
    <div class="flex items-center justify-between px-5 pt-3">
        <x-site.logo class="h-7 w-auto" style="--word: #0b4a36; --glyph: #fff" />
        <button type="button" x-ref="close" @click="close()" class="grid size-11 place-items-center rounded-full bg-[#062e22]/6 text-[#062e22] hover:bg-[#062e22]/12" aria-label="Close">
            <x-dashboard.icon name="x" class="size-6" />
        </button>
    </div>

    <template x-if="pass">
        <div class="flex flex-1 flex-col items-center justify-center px-6 pb-10 text-center">
            <h2 id="pickup-qr-title" class="text-[11px] font-semibold tracking-[0.18em] text-[#7a4e22] uppercase">Pickup QR · show at the counter</h2>
            <div class="mt-4 aspect-square w-[min(75vw,60vh,420px)] [&_svg]:size-full" x-html="pass.qr"></div>
            <p class="mt-5 font-mono text-[30px] font-bold tracking-[0.14em] tabular-nums" x-text="pass.pickup_code_display"></p>
            <p class="mt-2 text-[14px] text-[#062e22]/70">
                <span class="font-mono" x-text="pass.id"></span> · <span x-text="pass.branch_full"></span> · <span x-text="'Pickup ' + pass.pickup_time"></span>
            </p>
            <p class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#062e22]/6 px-3 py-1.5 text-[12.5px] text-[#062e22]/75">
                <x-dashboard.icon name="sun" class="size-4" /> Turn up your screen brightness for faster scanning.
            </p>
        </div>
    </template>
</div>

{{-- QR scanner (pickupVerification scope): the browser's own BarcodeDetector where it exists, else a demo scan. --}}
<div {{ $attributes }}>
    <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-forest-950 text-cream" aria-live="polite">
        <video x-ref="video" playsinline muted class="absolute inset-0 size-full object-cover" x-show="camera === 'live'" x-cloak></video>
        <div class="brand-pattern absolute inset-0 opacity-[0.06]" x-show="camera !== 'live'" aria-hidden="true"></div>

        {{-- Viewfinder: corner brackets and a moving line while scanning --}}
        <div class="pointer-events-none absolute inset-[14%]" x-show="['live', 'simulated', 'requesting'].includes(camera)" x-cloak aria-hidden="true">
            <span class="absolute top-0 left-0 size-9 rounded-tl-lg border-t-4 border-l-4 border-sand-400"></span>
            <span class="absolute top-0 right-0 size-9 rounded-tr-lg border-t-4 border-r-4 border-sand-400"></span>
            <span class="absolute bottom-0 left-0 size-9 rounded-bl-lg border-b-4 border-l-4 border-sand-400"></span>
            <span class="absolute right-0 bottom-0 size-9 rounded-br-lg border-r-4 border-b-4 border-sand-400"></span>
            <span class="scan-line absolute inset-x-3 h-0.5 rounded-full bg-sand-300 shadow-[0_0_14px_3px_rgb(230_190_126/0.65)]" x-show="camera !== 'requesting'"></span>
        </div>

        <div class="absolute inset-0 grid place-items-center p-6 text-center">
            <div x-show="camera === 'off'">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-white/8 text-sand-300"><x-dashboard.icon name="scan-qr-code" class="size-7" /></span>
                <p class="mt-3 font-display text-[15px] font-semibold">Scan the customer’s QR code</p>
                <p class="mt-1 text-[12.5px] text-cream/60">Ask the customer to turn up their screen brightness.</p>
            </div>
            <div x-show="camera === 'requesting'" x-cloak>
                <x-dashboard.icon name="loader-circle" class="mx-auto size-7 animate-spin text-sand-300" />
                <p class="mt-3 text-[13.5px] font-medium">Requesting camera…</p>
                <p class="mt-1 text-[12px] text-cream/60">Allow camera access when the browser asks.</p>
            </div>
            <p x-show="camera === 'simulated'" x-cloak class="self-end rounded-full bg-forest-950/70 px-3 py-1 text-[12px] font-medium text-sand-200">Scanning… <span class="text-cream/60">(demo)</span></p>
            <p x-show="camera === 'live'" x-cloak class="self-end rounded-full bg-forest-950/70 px-3 py-1 text-[12px] font-medium">Hold the QR inside the frame</p>
            <div x-show="camera === 'denied'" x-cloak class="max-w-xs">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-danger/20 text-red-200"><x-dashboard.icon name="camera-off" class="size-6" /></span>
                <p class="mt-3 text-[14px] font-semibold">Camera access denied</p>
                <p class="mt-1 text-[12.5px] text-cream/70">Allow the camera from the icon in the browser’s address bar, then scan again — or type the code below.</p>
            </div>
            <div x-show="camera === 'none'" x-cloak class="max-w-xs">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-white/8 text-sand-300"><x-dashboard.icon name="camera-off" class="size-6" /></span>
                <p class="mt-3 text-[14px] font-semibold">No camera found</p>
                <p class="mt-1 text-[12.5px] text-cream/70">Enter the customer’s pickup code below instead.</p>
            </div>
        </div>

        {{-- Camera controls --}}
        <div class="absolute top-3 right-3 flex gap-2" x-show="['live', 'simulated'].includes(camera)" x-cloak>
            <button type="button" class="grid size-11 place-items-center rounded-full bg-forest-950/70 text-cream hover:bg-forest-950" @click="toggleTorch()" :aria-pressed="torch" :aria-label="torch ? 'Turn flashlight off' : 'Turn flashlight on'">
                <span x-html="$icon(torch ? 'flashlight' : 'flashlight-off', 'size-5' + (torch ? ' text-sand-300' : ''))"></span>
            </button>
            <button type="button" class="grid size-11 place-items-center rounded-full bg-forest-950/70 text-cream hover:bg-forest-950" @click="switchCamera()" aria-label="Switch camera"><x-dashboard.icon name="switch-camera" class="size-5" /></button>
            <button type="button" class="grid size-11 place-items-center rounded-full bg-forest-950/70 text-cream hover:bg-forest-950" @click="stopCamera()" aria-label="Stop scanning"><x-dashboard.icon name="x" class="size-5" /></button>
        </div>
    </div>

    <div class="mt-3 flex gap-2">
        <button type="button" class="btn-primary h-12 flex-1 text-[15px]" @click="scan()" :disabled="busy || cooling">
            <x-dashboard.icon name="scan-qr-code" class="size-5" /> <span x-text="camera === 'denied' || camera === 'none' ? 'Try the camera again' : 'Scan QR code'"></span>
        </button>
        <button type="button" class="btn-secondary h-12" @click="simulate()" :disabled="busy || cooling" title="Demo: simulate a scan (choose the order in the demo panel)">
            <x-dashboard.icon name="sparkles" class="size-4" /> Demo scan
        </button>
    </div>
    <p class="mt-2 text-[11.5px] text-ink-faint" x-show="!cameraSupported" x-cloak>This browser can’t read QR codes from the camera (Chrome on Android and Mac can). Use the code, or the demo scan.</p>
</div>

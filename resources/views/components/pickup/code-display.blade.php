{{-- The KB pickup code, large and split for reading aloud (KB-B7F 42X); copy uses the plain code. Needs `pass` in scope. --}}
@props(['compact' => false])

<div x-data="pickupCode" {{ $attributes->class($compact ? '' : 'text-center') }}>
    <p class="eyebrow">Your pickup code</p>
    <button type="button" @click="speak(pass)" :disabled="!canSpeak" :title="canSpeak ? 'Tap to hear the code' : null"
            class="mt-1.5 rounded-lg font-mono font-bold tracking-[0.14em] tabular-nums text-ink transition-colors enabled:hover:text-accent {{ $compact ? 'text-[26px]' : 'text-[34px] sm:text-[42px]' }}"
            :aria-label="'Pickup code ' + pass.pickup_code.split('').join(' ') + (canSpeak ? '. Tap to hear it.' : '')"
            x-text="pass.pickup_code_display"></button>
    @unless ($compact)
        <p class="mx-auto mt-1 max-w-xs text-[13px] text-ink-soft">Show this code or the QR code to the café when you arrive to collect your order.</p>
    @endunless
    <div class="mt-3 flex flex-wrap gap-2 {{ $compact ? '' : 'justify-center' }}">
        <button type="button" class="btn-secondary btn-sm" @click="copy(pass)" :aria-label="copied ? 'Copied' : 'Copy code'">
            <span x-html="$icon(copied ? 'check' : 'copy', 'size-3.5' + (copied ? ' text-success' : ''))"></span><span x-text="copied ? 'Copied ✓' : 'Copy code'"></span>
        </button>
        <button type="button" class="btn-secondary btn-sm" x-show="canShare" x-cloak @click="share(pass)"><x-dashboard.icon name="share-2" class="size-3.5" /> Share</button>
        <button type="button" class="btn-ghost btn-sm" x-show="canSpeak" x-cloak @click="speak(pass)"><x-dashboard.icon name="volume-2" class="size-3.5" /> Read aloud</button>
    </div>
</div>

{{-- KB code entry (pickupVerification scope): fixed "KB-" prefix, six characters, Enter searches. --}}
<form {{ $attributes }} @submit.prevent="search('code')" novalidate>
    <label for="pickup-code" class="label">Or enter pickup code</label>
    <div class="flex items-stretch overflow-hidden rounded-xl border-2 bg-panel transition-colors focus-within:ring-4 focus-within:ring-accent/15"
         :class="[shaking ? 'is-shaking' : '', (badChars.length || ['not_found', 'invalid'].includes(state)) ? 'border-danger' : 'border-line-strong focus-within:border-accent']">
        <span class="grid place-items-center bg-subtle px-3.5 font-mono text-[22px] font-bold text-ink-faint select-none" aria-hidden="true">KB-</span>
        <input id="pickup-code" x-ref="code" type="text" :value="input" @input="onInput($event)"
               maxlength="40" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" enterkeyhint="search"
               placeholder="7F42X9" aria-describedby="pickup-code-hint"
               class="h-14 min-w-0 flex-1 border-0 bg-transparent px-3 font-mono text-[24px] font-semibold tracking-[0.3em] text-ink uppercase placeholder:font-normal placeholder:tracking-[0.3em] placeholder:text-ink-faint/50 focus:ring-0">
        <span class="grid place-items-center pr-3 text-[12px] tabular-nums text-ink-faint" x-text="input.length + '/6'" aria-hidden="true"></span>
    </div>

    <p id="pickup-code-hint" class="mt-2 min-h-5 text-[12.5px]">
        <span x-show="cooling" x-cloak class="font-medium text-danger">Too many wrong codes. Try again in <span class="tabular-nums" x-text="cooldownLeft"></span> s.</span>
        <span x-show="!cooling && badChars.length" x-cloak class="text-danger">Codes never contain O, 0, I, 1 or L — check “<span x-text="badChars.join(', ')"></span>”.</span>
        <span x-show="!cooling && !badChars.length" class="text-ink-faint">6 characters, with or without “KB-”. <kbd class="rounded border border-line bg-subtle px-1 font-sans text-[11px]">/</kbd> to type · <kbd class="rounded border border-line bg-subtle px-1 font-sans text-[11px]">Enter</kbd> to search · <kbd class="rounded border border-line bg-subtle px-1 font-sans text-[11px]">Esc</kbd> to clear</span>
    </p>

    <button type="submit" class="btn-primary mt-2 h-12 w-full text-[15px]" :disabled="!valid || cooling || busy">
        <span x-html="$icon(state === 'searching' ? 'loader-circle' : 'search', 'size-5' + (state === 'searching' ? ' animate-spin' : ''))"></span>
        <span x-text="state === 'searching' ? 'Searching…' : 'Search order'"></span>
    </button>
</form>

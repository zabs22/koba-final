@props(['model', 'label' => null])

{{-- Switch bound to an Alpine expression. --}}
<button type="button" role="switch" :aria-checked="{{ $model }} ? 'true' : 'false'" @if ($label) aria-label="{{ $label }}" @endif
        {{ $attributes->merge(['class' => 'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors']) }}
        :class="{{ $model }} ? 'bg-accent' : 'bg-ink/15'">
    <span class="inline-block size-4 rounded-full bg-white shadow transition-transform dark:bg-panel" :class="{{ $model }} ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
</button>

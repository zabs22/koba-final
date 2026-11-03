@props(['options' => \App\Support\Dashboard\DateRange::OPTIONS])

{{-- Date range filter for a page component (calls setRange on the enclosing `page`). --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}
     x-data="{ custom: filters.range === 'custom', from: filters.from, to: filters.to }">
    <div class="segmented max-w-full overflow-x-auto" role="group" aria-label="Date range">
        @foreach ($options as $key => $label)
            @if ($key === 'custom')
                <button type="button" :aria-pressed="filters.range === 'custom' || custom" @click="custom = !custom">
                    <span class="inline-flex items-center gap-1"><x-dashboard.icon name="calendar" class="size-3.5" />{{ $label }}</span>
                </button>
            @else
                <button type="button" :aria-pressed="filters.range === '{{ $key }}' && !custom" @click="custom = false; setRange({ range: '{{ $key }}' })">{{ $label }}</button>
            @endif
        @endforeach
    </div>
    <form x-show="custom" x-cloak x-transition.opacity class="flex items-center gap-1.5" @submit.prevent="setRange({ range: 'custom', from, to })">
        <label class="sr-only" for="range-from">From</label>
        <input id="range-from" type="date" class="field h-8 w-[9.5rem] py-0 text-[13px]" x-model="from" :max="to" required>
        <span class="text-ink-faint">–</span>
        <label class="sr-only" for="range-to">To</label>
        <input id="range-to" type="date" class="field h-8 w-[9.5rem] py-0 text-[13px]" x-model="to" :min="from" required>
        <button class="btn-primary btn-sm">Apply</button>
    </form>
</div>

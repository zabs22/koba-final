{{-- Confirmed → Preparing → Ready → Picked up. Needs the tracker scope (order, stepIndex(), progress()). --}}
@php
    $steps = [['pending', 'Confirmed', 'receipt-text'], ['preparing', 'Preparing', 'chef-hat'], ['ready', 'Ready', 'package-check'], ['completed', 'Picked up', 'circle-check']];
@endphp

<div {{ $attributes->class('relative') }}>
    <div class="absolute top-[18px] right-[12.5%] left-[12.5%] h-1 rounded-full bg-ink/10"></div>
    <div class="absolute top-[18px] left-[12.5%] h-1 rounded-full transition-[width] duration-700" :class="order.status === 'cancelled' ? 'bg-danger' : 'bg-forest-500'" :style="`width: ${progress() * 0.75}%`"></div>
    <ol class="relative grid grid-cols-4">
        @foreach ($steps as $i => [$status, $label, $icon])
            <li class="flex flex-col items-center text-center" :aria-current="stepIndex() === {{ $i }} ? 'step' : null">
                <span class="grid size-10 place-items-center rounded-full border-2 transition-colors duration-500"
                      :class="stepIndex() > {{ $i }} ? 'border-forest-500 bg-forest-500 text-white' : (stepIndex() === {{ $i }} ? 'border-forest-500 bg-panel text-forest-500 ring-4 ring-forest-500/15 dark:text-forest-300' : 'border-line-strong bg-panel text-ink-faint')">
                    <x-dashboard.icon :name="$icon" class="size-[18px]" />
                </span>
                <span class="mt-2 text-[12.5px] font-medium" :class="stepIndex() >= {{ $i }} ? 'text-ink' : 'text-ink-faint'">{{ $label }}</span>
                <span class="text-[11px] tabular-nums text-ink-faint" x-text="order.timeline[{{ $i }}].time ?? ''"></span>
            </li>
        @endforeach
    </ol>
</div>

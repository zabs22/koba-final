@props(['icon' => 'coffee', 'title', 'body' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-3 grid size-12 place-items-center rounded-full bg-subtle text-ink-faint ring-1 ring-line"><x-dashboard.icon :name="$icon" class="size-5" /></span>
    <p class="font-display text-[15px] font-semibold text-ink">{{ $title }}</p>
    @if ($body)<p class="mt-1 max-w-sm text-[13px] text-ink-soft">{{ $body }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>

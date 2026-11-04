@props(['title', 'eyebrow' => null, 'description' => null, 'breadcrumbs' => []])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0">
        @if ($breadcrumbs)
            <nav aria-label="Breadcrumb" class="mb-2">
                <ol class="flex flex-wrap items-center gap-1 text-xs text-ink-faint">
                    @foreach ($breadcrumbs as $label => $href)
                        <li class="flex items-center gap-1">
                            @if ($href)<a href="{{ $href }}" class="hover:text-ink">{{ $label }}</a>@else<span aria-current="page" class="text-ink-soft">{{ $label }}</span>@endif
                            @unless ($loop->last)<x-dashboard.icon name="chevron-right" class="size-3" />@endunless
                        </li>
                    @endforeach
                </ol>
            </nav>
        @elseif ($eyebrow)
            <p class="eyebrow mb-1.5">{{ $eyebrow }}</p>
        @endif
        <h1 class="font-display text-[26px] leading-tight font-semibold tracking-tight text-ink sm:text-[28px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 max-w-2xl text-[13.5px] text-ink-soft">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>

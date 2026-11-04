@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'card flex min-w-0 flex-col']) }}>
    @if ($title || isset($actions))
        <header class="card-head">
            <div class="min-w-0">
                @if ($title)<h2 class="card-title">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="card-sub">{{ $subtitle }}</p>@endif
                {{ $meta ?? '' }}
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['flex-1', 'px-5 pb-5 pt-1' => $padded])>
        {{ $slot }}
    </div>
</section>

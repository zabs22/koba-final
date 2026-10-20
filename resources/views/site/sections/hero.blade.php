@php
    // Background slideshow: cakes, pastries and drinks from the menu, with a focal point for the crop.
    $slides = [
        ['cake-opera-chocolate', '50% 55%'],
        ['glazed-dome', '50% 60%'],
        ['eclair', '50% 50%'],
        ['cake-red-velvet', '50% 55%'],
        ['mocha-slice', '50% 55%'],
        ['drink-cappuccino', '50% 50%'],
        ['almond-croissant', '50% 50%'],
    ];
@endphp

<section class="hero" aria-labelledby="hero-title" data-hero data-hero-slider data-interval="6000">
    <div class="hero__slides" aria-hidden="true">
        @foreach ($slides as $i => [$key, $position])
            <div class="hero__slide {{ $i === 0 ? 'is-active' : '' }}" data-slide>
                <x-site.img :src="$key" :eager="$i === 0" alt="" sizes="100vw" :position="$position" />
            </div>
        @endforeach
    </div>
    <div class="hero__shade" aria-hidden="true"></div>

    <div class="hero__copy">
        <p class="eyebrow" data-reveal style="--d: 1">{{ __('koba.hero.eyebrow') }}</p>

        <h1 id="hero-title" class="display hero__title" data-reveal="lines" style="--d: 2">
            @foreach (trans('koba.hero.lines') as $i => $line)
                <span class="line"><span style="--l: {{ $i }}">
                    @if ($line['weight'] === 'light')<em>{{ $line['text'] }}</em>@else{{ $line['text'] }}@endif
                </span></span>
            @endforeach
        </h1>

        <p class="lede hero__lede" data-reveal style="--d: 6">{{ __('koba.hero.sub') }}</p>

        <div class="btn-row" data-reveal style="--d: 7">
            <a class="btn btn--sand" href="{{ route('menu') }}">{{ __('koba.hero.cta_menu') }} <x-site.arrow /></a>
            <a class="btn btn--ghost" href="{{ route('order') }}">{{ __('koba.hero.cta_cake') }}</a>
        </div>
    </div>

    {{-- The koba leaf band, with the croissant rising out of it in front. --}}
    <div class="hero__stage">
        <x-site.img src="hero-leaves-alpha" class="hero__leaves" :eager="true" alt="" sizes="100vw" />
        <div class="hero__subject">
            <x-site.img src="hero-croissant" :eager="true" sizes="(min-width: 900px) 52vw, 86vw" />
            <div class="hero__badge" aria-hidden="true">
                <svg class="hero__badge-ring" viewBox="0 0 200 200">
                    <defs><path id="hero-badge-path" d="M100 100m-80 0a80 80 0 1 1 160 0a80 80 0 1 1-160 0" /></defs>
                    <text><textPath href="#hero-badge-path" textLength="500" lengthAdjust="spacing">{{ __('koba.hero.badge') }}</textPath></text>
                </svg>
                <x-site.mark class="hero__badge-mark" />
            </div>
        </div>
    </div>

    <div class="hero__controls">
        <button type="button" class="hero__arrow" data-slide-prev aria-label="{{ __('koba.ui.prev') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <ol class="list-reset hero__dots" aria-label="{{ __('koba.ui.slides') }}">
            @foreach ($slides as $i => $slide)
                <li>
                    <button type="button" class="hero__dot {{ $i === 0 ? 'is-active' : '' }}" data-slide-to="{{ $i }}"
                            aria-label="{{ __('koba.ui.slide', ['n' => $i + 1, 'total' => count($slides)]) }}" @if ($i === 0) aria-current="true" @endif>
                        <span class="index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <i aria-hidden="true"></i>
                    </button>
                </li>
            @endforeach
        </ol>
        <button type="button" class="hero__arrow" data-slide-next aria-label="{{ __('koba.ui.next') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="hero__arrow hero__pause" data-slide-pause
                data-label-pause="{{ __('koba.ui.pause') }}" data-label-play="{{ __('koba.ui.play') }}" aria-label="{{ __('koba.ui.pause') }}">
            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path class="i-pause" d="M7 5h3v14H7zM14 5h3v14h-3z" fill="currentColor"/><path class="i-play" d="M8 5l11 7-11 7z" fill="currentColor"/></svg>
        </button>
    </div>

    <a class="hero__scroll" href="#intro">
        <span>{{ __('koba.ui.scroll') }}</span>
        <i aria-hidden="true"></i>
    </a>
</section>

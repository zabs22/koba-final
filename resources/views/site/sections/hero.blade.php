@php
    // The signature cakes, each named as on the menu. [cake slug, image, focal point]
    $notes = trans('koba.menu_notes');
    $cakes = trans('koba.cakes');
    $slides = collect([
        ['chocolate-fudge', '50% 52%'],
        ['trio', '50% 55%'],
        ['opera-caramel', '50% 58%'],
        ['cheesecake', '50% 55%'],
        ['vanilla-eclair', '50% 55%'],
    ])->map(fn ($s) => [
        'slug' => $s[0],
        'image' => $cakes[$s[0]]['image'],
        'position' => $s[1],
        'title' => $notes[$s[0]]['title'] ?? $cakes[$s[0]]['name'],
        'name' => $cakes[$s[0]]['name'],
    ]);
@endphp

<section class="hero" aria-labelledby="hero-title" data-hero data-hero-slider data-interval="6000">
    <div class="hero__copy">
        <p class="eyebrow" data-reveal style="--d: 1">{{ __('koba.hero.eyebrow') }}</p>

        <h1 id="hero-title" class="display hero__title" data-reveal="lines" style="--d: 2">
            <span class="line"><span style="--l: 0">{{ __('koba.hero.simple.0') }}</span></span>
            <span class="line"><span style="--l: 1"><em>{{ __('koba.hero.simple.1') }}</em></span></span>
        </h1>

        <p class="lede hero__lede" data-reveal style="--d: 5">{{ __('koba.hero.simple_sub') }}</p>

        <div class="btn-row" data-reveal style="--d: 6">
            <a class="btn btn--sand" href="{{ route('menu') }}">{{ __('koba.hero.cta_menu') }} <x-site.arrow /></a>
            <a class="btn btn--ghost" href="{{ route('order') }}">{{ __('koba.hero.cta_cake') }}</a>
        </div>
    </div>

    {{-- The cakes: each slide wipes in over the last, with its house name. --}}
    <div class="hero__stage">
        <div class="hero__slides">
            @foreach ($slides as $i => $slide)
                <figure class="hero__slide {{ $i === 0 ? 'is-active' : '' }}" data-slide>
                    <x-site.img :src="$slide['image']" :eager="true" alt="" sizes="(min-width: 900px) 52vw, 100vw" :position="$slide['position']" />
                    <figcaption class="hero__caption">
                        <span class="hero__caption-no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        <a class="hero__caption-title" href="{{ route('cakes') }}#cake-{{ $slide['slug'] }}">{{ $slide['title'] }}</a>
                        <span class="hero__caption-name">{{ $slide['name'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <div class="hero__controls">
            <button type="button" class="hero__arrow" data-slide-prev aria-label="{{ __('koba.ui.prev') }}">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <ol class="list-reset hero__dots" aria-label="{{ __('koba.ui.slides') }}">
                @foreach ($slides as $i => $slide)
                    <li>
                        <button type="button" class="hero__dot {{ $i === 0 ? 'is-active' : '' }}" data-slide-to="{{ $i }}"
                                aria-label="{{ __('koba.ui.slide', ['n' => $i + 1, 'total' => $slides->count()]) }}" @if ($i === 0) aria-current="true" @endif>
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
    </div>

    <a class="hero__scroll" href="#intro">
        <span>{{ __('koba.ui.scroll') }}</span>
        <i aria-hidden="true"></i>
    </a>
</section>

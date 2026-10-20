{{-- Brand values: a pinned horizontal track on desktop, a swipeable rail elsewhere.
     Behaviour: resources/js/site/home.js → initPillars --}}
@php($pillars = trans('koba.pillars.items'))

<section class="pillars is-deep" aria-labelledby="pillars-title" data-pillars data-chapter="{{ __('koba.chapters.pillars') }}" data-chapter-tone="dark" style="--n: {{ count($pillars) }}; --tone: {{ $pillars[0]['tone'] }}">
    <div class="pillars__pin">
        <div class="pillars__glow" aria-hidden="true"></div>

        <div class="wrap grid pillars__head">
            <div class="pillars__heading">
                <p class="eyebrow" data-reveal>{{ __('koba.pillars.eyebrow') }}</p>
                <x-site.heading id="pillars-title" :text="__('koba.pillars.title')" size="l" />
            </div>
            <div class="pillars__aside">
                <p class="lede" data-reveal>{{ __('koba.pillars.body') }}</p>
                <p class="pillars__count" aria-hidden="true"><span data-pillars-count>01</span> / {{ str_pad(count($pillars), 2, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>

        <div class="pillars__viewport">
            <ol class="list-reset pillars__track" data-pillars-track tabindex="0" aria-label="{{ __('koba.pillars.eyebrow') }}">
                @foreach ($pillars as $i => $pillar)
                    <li class="pillar" data-pillar data-tone="{{ $pillar['tone'] }}">
                        <article class="pillar__card">
                            <div class="pillar__media">
                                <x-site.img :src="$pillar['image']" alt="" sizes="(min-width: 900px) 34vw, 78vw" />
                            </div>
                            <div class="pillar__body">
                                <span class="pillar__no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="pillar__name">{{ $pillar['name'] }}</h3>
                                <p class="pillar__line">{{ $pillar['line'] }}</p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="wrap pillars__foot" aria-hidden="true">
            <ol class="list-reset pillars__ticks">
                @foreach ($pillars as $pillar)
                    <li data-pillars-tick><span>{{ $pillar['name'] }}</span></li>
                @endforeach
            </ol>
            <p class="pillars__hint">{{ __('koba.pillars.hint') }}</p>
        </div>
    </div>
</section>

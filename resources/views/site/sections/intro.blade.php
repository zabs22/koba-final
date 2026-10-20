{{-- Rituals: a day at KOBA, morning to evening. Pinned and scroll-driven on desktop
     (resources/js/site/home.js → initRituals); a simple stack everywhere else. --}}
@php($moments = trans('koba.intro.moments'))

<section class="rituals" id="intro" aria-labelledby="intro-title" data-rituals data-chapter="{{ __('koba.chapters.intro') }}" style="--n: {{ count($moments) }}">
    <div class="rituals__pin">
        <div class="rituals__sky" aria-hidden="true">
            <x-site.leaf class="rituals__leaf rituals__leaf--a" />
            <x-site.leaf class="rituals__leaf rituals__leaf--b" />
            <span class="rituals__glyph ethiopic">ኮባ</span>
        </div>

        <div class="wrap grid rituals__grid">
            <div class="rituals__copy">
                <p class="rituals__label" data-reveal>
                    <span class="eyebrow">{{ __('koba.intro.eyebrow') }}</span>
                    <span class="rituals__est">{{ __('koba.intro.founded') }}</span>
                </p>
                <x-site.heading id="intro-title" :text="__('koba.intro.title')" size="xl" />
                <p class="lede" data-reveal style="--d: 2">{{ __('koba.intro.lede') }}</p>

                <ol class="list-reset rituals__list" aria-label="{{ __('koba.intro.day_label') }}">
                    @foreach ($moments as $i => $moment)
                        <li class="rituals__item {{ $i === 0 ? 'is-active' : '' }}" data-ritual data-reveal style="--d: {{ $i + 3 }}">
                            <span class="rituals__time">{{ $moment['time'] }}</span>
                            <span class="rituals__part">{{ $moment['part'] }}</span>
                            <span class="rituals__text">{{ trans('koba.intro.rituals')[$i] }}</span>
                            <i class="rituals__bar" aria-hidden="true"></i>
                        </li>
                    @endforeach
                </ol>

                <a class="link" href="{{ route('our-story') }}" data-reveal style="--d: 6">{{ __('koba.nav.our-story') }} <x-site.arrow /></a>
            </div>

            <div class="rituals__stage" aria-hidden="true">
                {{-- The sun travels the arc as the day moves on. --}}
                <div class="rituals__horizon">
                    <svg class="rituals__arc" viewBox="0 0 600 320" focusable="false">
                        <path class="rituals__arc-track" d="M20 300A280 280 0 0 1 580 300" pathLength="1" />
                        <path class="rituals__arc-done" d="M20 300A280 280 0 0 1 580 300" pathLength="1" />
                        <line x1="0" y1="300" x2="600" y2="300" />
                    </svg>
                    <span class="rituals__sun"><i></i></span>
                </div>

                <div class="rituals__deck">
                    @foreach ($moments as $i => $moment)
                        <figure class="rituals__card" data-ritual-card style="--i: {{ $i }}">
                            <div class="rituals__photo">
                                <x-site.img :src="$moment['image']" alt="" sizes="(min-width: 900px) 30vw, 70vw" :position="$moment['position']" />
                            </div>
                            <figcaption>
                                <span class="index">{{ $moment['time'] }}</span>
                                <strong>{{ $moment['word'] }}</strong>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

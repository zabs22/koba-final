<x-site.layout page="experience" :title="__('koba.meta.pages.experience')" :description="__('koba.experience_page.lede')" image="drink-iced-mocha">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.experience_page.eyebrow'),
        'title' => __('koba.experience_page.title'),
        'lede' => __('koba.experience_page.lede'),
        'slides' => [['drink-iced-latte-terrace', '50% 45%'], ['drink-tea-pour', '50% 55%'], ['drink-iced-mocha', '50% 50%'], ['drink-layered-terrace', '50% 55%']],
    ])

    @php($xp = trans('koba.experience_page'))

    <div class="xp" data-xp>

        {{-- 01 · The ritual — statement over layered koba leaves --}}
        <section class="xp-intro section" aria-labelledby="xp-intro-title">
            <div class="xp-intro__leaves" aria-hidden="true">
                <div class="xp-intro__layer" data-parallax="-0.12">@include('site.partials.leaf', ['class' => 'xp-leaf xp-leaf--1', 'id' => 'intro-1'])</div>
                <div class="xp-intro__layer" data-parallax="0.08">@include('site.partials.leaf', ['class' => 'xp-leaf xp-leaf--2', 'id' => 'intro-2'])</div>
                <div class="xp-intro__layer" data-parallax="-0.2">@include('site.partials.leaf', ['class' => 'xp-leaf xp-leaf--3', 'id' => 'intro-3'])</div>
            </div>
            <div class="wrap grid xp-intro__grid">
                <div class="xp-intro__copy">
                    <p class="eyebrow" data-reveal>{{ $xp['intro_eyebrow'] }}</p>
                    <x-site.heading id="xp-intro-title" :text="$xp['intro_title']" size="l" />
                    <p class="lede" data-reveal style="--d: 2">{{ $xp['intro_body'] }}</p>
                </div>
                <dl class="xp-facts">
                    @foreach ($xp['intro_facts'] as $fact)
                        <div class="xp-facts__item" data-reveal style="--d: {{ $loop->index + 2 }}">
                            <dt>{{ $fact['label'] }}</dt>
                            <dd class="display">{{ $fact['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- 02 · A day at KOBA — pinned horizontal journey, morning → evening --}}
        <section class="xp-day" data-xp-day aria-label="{{ $xp['day_eyebrow'] }}" style="--acts: {{ count($xp['day']) }}">
            <div class="xp-day__sticky">
                <div class="xp-day__sky" aria-hidden="true">
                    <svg viewBox="0 0 1000 130" preserveAspectRatio="none">
                        <path class="xp-day__arc" d="M30 122Q500-70 970 122" data-xp-arc/>
                        <path class="xp-day__arc xp-day__arc--done" d="M30 122Q500-70 970 122" data-xp-arc-done/>
                    </svg>
                    <span class="xp-day__sun" data-xp-sun></span>
                    <ol class="list-reset xp-day__marks">
                        @foreach ($xp['day'] as $moment)
                            <li class="{{ $loop->first ? 'is-on is-now' : '' }}" data-xp-mark>
                                <button class="xp-day__mark" type="button" tabindex="-1"><span class="xp-day__mark-hour">{{ $moment['hour'] }}</span><span class="xp-day__mark-dot"> · </span><span>{{ $moment['time'] }}</span></button>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="xp-day__track" data-xp-track>
                    @foreach ($xp['day'] as $i => $moment)
                        <article class="xp-act xp-act--{{ $i + 1 }}" aria-labelledby="xp-act-{{ $i }}" data-xp-act>
                            <div class="wrap grid xp-act__grid">
                                <div class="xp-act__copy">
                                    <p class="xp-act__time">
                                        <span class="index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad(count($xp['day']), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span>{{ $moment['time'] }}</span>
                                    </p>
                                    <x-site.heading id="xp-act-{{ $i }}" :text="$moment['title']" size="l" tag="h2" />
                                    <p class="lede">{{ $moment['body'] }}</p>
                                </div>
                                <div class="xp-stack" data-xp-tilt>
                                    <span class="xp-act__hour display" aria-hidden="true">{{ $moment['hour'] }}</span>
                                    @foreach ($moment['images'] as $image)
                                        <figure class="xp-stack__layer xp-stack__layer--{{ $loop->iteration }} media">
                                            <x-site.img :src="$image" sizes="(min-width: 900px) {{ $loop->first ? '32vw' : '16vw' }}, 70vw" />
                                        </figure>
                                    @endforeach
                                </div>
                                <button class="xp-act__shuffle" type="button" data-xp-shuffle aria-label="{{ $xp['day_shuffle'] }}">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12a7 7 0 1 1-2.05-4.95M19 4v4h-4" /></svg>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="xp-day__hint" aria-hidden="true"><span class="xp-day__hint-scroll">{{ $xp['day_hint'] }}</span><span class="xp-day__hint-phone">{{ $xp['day_hint_phone'] }}</span><span class="xp-day__hint-swipe">{{ $xp['day_swipe'] }} <x-site.arrow /></span></p>
            </div>
        </section>

        {{-- 03 · Five senses — tilt cards --}}
        <section class="xp-senses section is-deep" aria-labelledby="xp-senses-title">
            <div class="wrap">
                <header class="grid xp-head">
                    <div class="xp-head__title">
                        <p class="eyebrow" data-reveal>{{ $xp['senses_eyebrow'] }}</p>
                        <x-site.heading id="xp-senses-title" :text="$xp['senses_title']" size="l" />
                    </div>
                    <p class="lede xp-head__lede" data-reveal>{{ $xp['senses_body'] }}</p>
                </header>

                <ol class="list-reset xp-senses__grid">
                    @foreach ($xp['senses'] as $sense)
                        <li class="xp-sense" data-reveal style="--d: {{ $loop->index }}">
                            <div class="xp-sense__card" data-xp-tilt>
                                <div class="xp-sense__media">
                                    <x-site.img :src="$sense['image']" sizes="(min-width: 900px) 20vw, 80vw" />
                                </div>
                                <div class="xp-sense__body">
                                    <span class="xp-sense__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3 class="xp-sense__name">{{ $sense['name'] }}</h3>
                                    <p class="xp-sense__line">{{ $sense['line'] }}</p>
                                </div>
                                <span class="xp-sense__glare" aria-hidden="true"></span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- 04 · Atmosphere — 3D gallery ring --}}
        @php($ring = ['drink-iced-mocha', 'cake-trio', 'drink-layered-terrace', 'bomboloni', 'drink-iced-americano', 'drink-macchiato', 'drink-mocha', 'drink-affogato', 'drink-pineapple-mojito', 'drink-red-juice'])
        <section class="xp-ring section is-white has-trees"aria-labelledby="xp-ring-title" data-xp-ring>
            <div class="wrap">
                <header class="grid xp-head">
                    <div class="xp-head__title">
                        <p class="eyebrow" data-reveal>{{ $xp['atmosphere_eyebrow'] }}</p>
                        <x-site.heading id="xp-ring-title" :text="$xp['atmosphere_title']" size="l" />
                    </div>
                    <p class="lede xp-head__lede" data-reveal>{{ $xp['atmosphere_body'] }}</p>
                </header>
            </div>

            <div class="xp-ring__stage" data-xp-ring-stage style="--n: {{ count($ring) }}" tabindex="0" role="group" aria-roledescription="carousel" aria-label="{{ $xp['atmosphere_eyebrow'] }}. {{ $xp['atmosphere_hint'] }}">
                <div class="xp-ring__spin" data-xp-ring-spin>
                    @foreach ($ring as $image)
                        <figure class="xp-ring__card media" style="--i: {{ $loop->index }}" data-xp-ring-card>
                            <x-site.img :src="$image" sizes="(min-width: 900px) 18vw, 40vw" />
                        </figure>
                    @endforeach
                </div>
                <div class="xp-ring__floor" aria-hidden="true"></div>
            </div>
            <p class="xp-ring__hint" aria-hidden="true"><span></span>{{ $xp['atmosphere_hint'] }}<span></span></p>
        </section>

        {{-- 05 · Hospitality — the quote lights up word by word --}}
        <section class="xp-quote section is-cream-2" aria-label="{{ $xp['hospitality_eyebrow'] }}" data-xp-quote>
            <div class="wrap grid xp-quote__grid">
                <div class="xp-quote__art" aria-hidden="true">
                    @include('site.partials.leaf', ['class' => 'xp-leaf', 'id' => 'quote'])
                    <figure class="xp-quote__leaf" data-parallax="-0.06">
                        <x-site.img src="drink-tea-pour" alt="" sizes="(min-width: 900px) 30vw, 70vw" />
                    </figure>
                </div>
                <div class="xp-quote__copy">
                    <p class="eyebrow" data-reveal>{{ $xp['hospitality_eyebrow'] }}</p>
                    <blockquote class="xp-quote__text display">
                        <p>
                            @foreach (preg_split('/\s+/u', '“'.$xp['hospitality'].'”') as $word)
                                <span class="xp-word" style="--w: {{ $loop->index }}">{{ $word }}</span>
                            @endforeach
                        </p>
                    </blockquote>
                    <p class="xp-quote__sign" data-reveal>{{ $xp['hospitality_sign'] }}</p>
                </div>
            </div>
            <svg width="0" height="0" aria-hidden="true" focusable="false">
                <clipPath id="xp-leaf-clip" clipPathUnits="objectBoundingBox">
                    <path d="M.5 .006c.242 .084 .433 .234 .442 .453 .008 .206-.158 .391-.442 .534C.217 .85 .05 .666 .058 .459 .067 .241 .258 .091 .5 .006z"/>
                </clipPath>
            </svg>
        </section>

        {{-- 06 · Behind the counter — expanding craft strip --}}
        <section class="xp-craft section is-white has-trees"aria-labelledby="xp-craft-title" data-xp-craft>
            <div class="wrap">
                <header class="grid xp-head">
                    <div class="xp-head__title">
                        <p class="eyebrow" data-reveal>{{ $xp['craft_eyebrow'] }}</p>
                        <x-site.heading id="xp-craft-title" :text="$xp['craft_title']" size="l" />
                    </div>
                    <div class="xp-head__lede">
                        <p class="lede" data-reveal>{{ $xp['craft_body'] }}</p>
                        <a class="link" href="{{ route('menu') }}" data-reveal>{{ __('koba.creations.cta') }} <x-site.arrow /></a>
                    </div>
                </header>

                <ol class="list-reset xp-craft__strip" data-reveal="media">
                    @foreach ($xp['craft_steps'] as $step)
                        <li class="xp-step {{ $loop->first ? 'is-open' : '' }}" data-xp-step>
                            <button class="xp-step__btn" type="button" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                <x-site.img :src="$step['image']" sizes="(min-width: 900px) 40vw, 90vw" />
                                <span class="xp-step__shade" aria-hidden="true"></span>
                                <span class="xp-step__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="xp-step__name">{{ $step['name'] }}</span>
                                <span class="xp-step__line">{{ $step['line'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    </div>

    @include('site.sections.voices')
    @include('site.sections.final-cta')
</x-site.layout>

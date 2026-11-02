<x-site.layout page="experience" :title="__('koba.meta.pages.experience')" :description="__('koba.experience_page.lede')" image="drink-iced-mocha">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.experience_page.eyebrow'),
        'title' => __('koba.experience_page.title'),
        'lede' => __('koba.experience_page.lede'),
        'slides' => [['drink-iced-latte-terrace', '50% 45%'], ['drink-tea-pour', '50% 55%'], ['drink-iced-mocha', '50% 50%'], ['drink-layered-terrace', '50% 55%']],
    ])

    @php($xp = trans('koba.experience_page'))

    <div class="xp" data-xp>

        {{-- 01 · How you feel — a sentence that finishes three ways, each with its own photograph --}}
        <section class="xp-feel" aria-labelledby="xp-feel-title" data-xp-feel>
            <div class="wrap grid xp-feel__grid">
                <div class="xp-feel__copy">
                    <p class="eyebrow" data-reveal>{{ $xp['feel_eyebrow'] }}</p>
                    <x-site.heading id="xp-feel-title" :text="$xp['feel_title']" size="l" />

                    <p class="xp-feel__say" data-reveal style="--d: 2" aria-live="polite">
                        <span>{{ $xp['feel_lead'] }}</span>
                        <span class="xp-feel__slot">
                            @foreach ($xp['feel'] as $mood)
                                <span class="xp-feel__phrase {{ $loop->first ? 'is-on' : '' }}" data-xp-feel-phrase @unless ($loop->first) aria-hidden="true" @endunless>{{ $mood['phrase'] }}</span>
                            @endforeach
                        </span>
                    </p>

                    <ul class="list-reset xp-feel__picks" data-reveal style="--d: 3">
                        @foreach ($xp['feel'] as $mood)
                            <li><button class="xp-feel__pick" type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-xp-feel-pick>{{ $mood['pick'] }}</button></li>
                        @endforeach
                    </ul>

                    <div class="xp-feel__replies" data-reveal style="--d: 4">
                        @foreach ($xp['feel'] as $mood)
                            <p class="xp-feel__reply {{ $loop->first ? 'is-on' : '' }}" data-xp-feel-reply>{{ $mood['reply'] }}</p>
                        @endforeach
                    </div>
                </div>

                {{-- The table, laid for whichever reason brought you in. --}}
                <div class="xp-feel__scene" data-reveal style="--d: 2">
                    @include('site.partials.xp-table')
                </div>

                <p class="xp-feel__close" aria-label="{{ implode(', ', $xp['feel_close']) }}">
                    @foreach ($xp['feel_close'] as $word)
                        <span data-reveal style="--d: {{ $loop->index + 4 }}">{{ $word }}</span>
                    @endforeach
                </p>
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

                <p class="xp-day__hint" aria-hidden="true"><span class="xp-day__hint-scroll">{{ $xp['day_hint'] }}</span><span class="xp-day__hint-swipe">{{ $xp['day_swipe'] }} <x-site.arrow /></span></p>
            </div>
        </section>

        {{-- 03 · The third place — the drinks, poured into one round cup --}}
        <section class="xp-bar is-dark"aria-labelledby="xp-bar-title" data-xp-bar>
            <div class="wrap grid xp-bar__grid">
                <div class="xp-bar__copy">
                    <p class="eyebrow" data-reveal>{{ $xp['bar_eyebrow'] }}</p>
                    <x-site.heading id="xp-bar-title" :text="$xp['bar_title']" size="l" />
                    <p class="xp-bar__body" data-reveal style="--d: 2">{{ $xp['bar_body'] }}</p>
                    <ul class="list-reset xp-bar__list" data-reveal style="--d: 3">
                        @foreach ($xp['bar'] as $drink)
                            <li><button class="xp-bar__item" type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-xp-bar-item><span class="xp-bar__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $drink['name'] }}</button></li>
                        @endforeach
                    </ul>
                </div>

                <div class="xp-bar__cup" data-reveal="media">
                    <svg class="xp-bar__ring" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
                        <defs><path id="xp-bar-circle" d="M100 100m-92 0a92 92 0 1 1 184 0a92 92 0 1 1-184 0"/></defs>
                        <text><textPath href="#xp-bar-circle" textLength="574">&#160;{{ strtoupper(trim($xp['bar_ring'])) }}&#160;</textPath></text>
                    </svg>
                    <div class="xp-bar__well">
                        @foreach ($xp['bar'] as $drink)
                            <figure class="xp-bar__pour {{ $loop->first ? 'is-on' : '' }}" data-xp-bar-pour>
                                <x-site.img :src="$drink['image']" sizes="(min-width: 900px) 30vw, 70vw" />
                            </figure>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- 04 · Catering — occasions as place cards that turn over --}}
        <section class="xp-cater is-cream-2" aria-labelledby="xp-cater-title" data-xp-cater>
            {{-- Hovering a card opens its photograph out to fill the section behind the words. --}}
            <div class="xp-cater__stage" aria-hidden="true">
                @foreach ($xp['cater'] as $event)
                    <figure class="xp-cater__bg" data-xp-cater-bg>
                        <x-site.img :src="$event['image']" sizes="100vw" alt="" />
                    </figure>
                @endforeach
            </div>

            <div class="wrap grid xp-cater__grid">
                <div class="xp-cater__copy">
                    <p class="eyebrow" data-reveal>{{ $xp['cater_eyebrow'] }}</p>
                    <x-site.heading id="xp-cater-title" :text="$xp['cater_title']" size="l" />
                    <p class="xp-cater__body" data-reveal style="--d: 2">{{ $xp['cater_body'] }}</p>
                    <div class="xp-cater__actions" data-reveal style="--d: 3">
                        <a class="btn" href="{{ route('contact') }}">{{ $xp['cater_events'] }} <x-site.arrow /></a>
                        <a class="link" href="{{ route('order') }}">{{ $xp['cater_order'] }} <x-site.arrow /></a>
                    </div>
                </div>

                <ul class="list-reset xp-cater__cards">
                    @foreach ($xp['cater'] as $event)
                        <li class="xp-place" data-reveal style="--d: {{ $loop->index }}">
                            <button class="xp-place__card" type="button" aria-pressed="false" data-xp-place>
                                <x-site.img :src="$event['image']" sizes="(min-width: 900px) 20vw, 45vw" alt="" />
                                <span class="xp-place__text">
                                    <span class="xp-place__name">{{ $event['name'] }}</span>
                                    <span class="xp-place__more"><span class="xp-place__line">{{ $event['line'] }}</span></span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Earlier sections; intro, craft and reviews repeat the home and Our Story pages. --}}
        {{-- @include('site.partials.experience-classic') --}}
    </div>

    {{-- @include('site.sections.voices') --}}
    @include('site.sections.final-cta')
</x-site.layout>

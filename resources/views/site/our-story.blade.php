<x-site.layout page="our-story" :title="__('koba.meta.pages.our-story')" :description="__('koba.story.lede')" image="craft-crumb">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.story.eyebrow'),
        'title' => __('koba.story.title'),
        'lede' => __('koba.story.lede'),
        'slides' => [['croissant', '50% 55%'], ['craft-almond', '50% 45%'], ['craft-crumb', '50% 50%'], ['opera-slice', '50% 60%']],
    ])

    {{-- The pour: a cup filled as you scroll (resources/js/site/pour.js) --}}
    @include('site.sections.pour')

    {{-- Chapters --}}
    @php
        $seals = ['Est. 2020 · Addis Ababa · KOBA Patisserie & Bakery ·', 'Laminated · Glazed · Finished by hand ·', 'Sanford · Bole Atlas · 4 Killo · KOBA ·'];
    @endphp
    <section class="chapters section is-white" aria-label="{{ __('koba.story.eyebrow') }}">
        {{-- Café paper: brand pattern band + a drifting sand leaf --}}
        <div class="pattern-band chapters__band" aria-hidden="true"></div>
        <x-site.leaf class="chapters__leaf" data-parallax="0.06" />

        <div class="wrap">
            @foreach (trans('koba.story.chapters') as $i => $chapter)
                <article class="chapter grid {{ $i % 2 ? 'chapter--flip' : '' }} chapter--{{ $i }}">
                    <p class="chapter__no" data-reveal>{{ $chapter['no'] }}</p>
                    <div class="chapter__copy">
                        {{-- Menu-card index: 01 ·········· 2020 --}}
                        <p class="chapter__menu" aria-hidden="true" data-reveal>
                            <span>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><i></i><span>{{ $chapter['no'] }}</span>
                        </p>
                        @if ($i === 0)
                            <p class="chapter__eyebrow eyebrow" data-reveal style="--d: 0">Our Beginning</p>
                        @elseif ($i === 1)
                            <p class="chapter__eyebrow eyebrow" data-reveal style="--d: 0">The Craft</p>
                        @endif
                        <x-site.heading :text="$chapter['title']" tag="h2" size="m" />
                        <p class="body-copy" data-reveal style="--d: 2">{{ $chapter['body'] }}</p>
                        @if ($i === 1)
                            <p class="chapter__annotation" data-reveal style="--d: 3">Laminated. Glazed. Finished by hand.</p>
                        @endif
                    </div>
                    @if ($i === 0)
                        {{-- Chapter 0 uses a custom JPG with a dark-green blending gradient --}}
                        <figure class="chapter__media chapter__media--excellence" data-parallax="0.05">
                            <div class="media" data-reveal="media">
                                <img
                                    class="img"
                                    src="{{ asset('images/koba/excellence.jpg.png') }}"
                                    alt="Cross-section of a handcrafted KOBA croissant showing its airy layers"
                                    width="1189"
                                    height="597"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                            @include('site.partials.chapter-seal', ['i' => $i, 'text' => $seals[$i]])
                        </figure>
                    @elseif ($i === 1)
                        {{-- Chapter 1 uses custom image 12.png --}}
                        <figure class="chapter__media" data-parallax="-0.05">
                            <div class="media" data-reveal="media">
                                <img
                                    class="img"
                                    src="{{ asset('images/koba/12.png') }}"
                                    alt="Layers of a handcrafted KOBA pastry showing precision and craftsmanship"
                                    width="760"
                                    height="520"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                            @include('site.partials.chapter-seal', ['i' => $i, 'text' => $seals[$i]])
                        </figure>
                    @else
                        <figure class="chapter__media" data-parallax="{{ $i % 2 ? '-0.05' : '0.05' }}">
                            <div class="media" data-reveal="media">
                                <x-site.img :src="$chapter['image']" sizes="(min-width: 900px) 40vw, 90vw" />
                            </div>
                            @include('site.partials.chapter-seal', ['i' => $i, 'text' => $seals[$i] ?? 'KOBA Patisserie & Bakery ·'])
                            <span class="chapter__ring" aria-hidden="true"></span>
                        </figure>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    {{-- The name: ኮባ --}}
    <section class="namesake section is-sand" aria-labelledby="namesake-title">
        <x-site.leaf class="namesake__leaf" data-parallax="0.08" />
        <div class="wrap grid namesake__grid">
            <div class="namesake__mark" data-reveal="draw">
                <x-site.mark />
                <span class="ethiopic namesake__glyph" aria-hidden="true">ኮባ</span>
            </div>
            <div class="namesake__copy">
                <p class="eyebrow" data-reveal>{{ __('koba.story.leaf_eyebrow') }}</p>
                <x-site.heading id="namesake-title" :text="__('koba.story.leaf_title')" size="l" />
                @foreach (trans('koba.story.leaf_body') as $paragraph)
                    <p class="body-copy" data-reveal style="--d: {{ $loop->iteration }}">{{ $paragraph }}</p>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Purpose, mission, vision — interactive expanding cards --}}
    @php
        $pmvItems = [
            'purpose' => ['img' => 'craft-lamination', 'icon' => '✦'],
            'mission' => ['img' => 'almond-croissant',  'icon' => '◈'],
            'vision'  => ['img' => 'cake-opera-caramel','icon' => '◉'],
        ];
    @endphp
    <section class="pmv-section section is-dark" aria-label="{{ __('koba.story.purpose_eyebrow') }}">
        <div class="wrap">
            <div class="pmv-deck" data-pmv-deck>
                @foreach ($pmvItems as $key => $meta)
                    @php($idx = $loop->index)
                    <div class="pmv-panel {{ $loop->first ? 'is-open' : '' }}"
                         data-pmv-panel
                         role="button"
                         tabindex="0"
                         aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                         style="--i: {{ $idx }}">

                        {{-- collapsed label (visible when closed) --}}
                        <div class="pmv-panel__label">
                            <span class="pmv-panel__icon">{{ $meta['icon'] }}</span>
                            <span class="pmv-panel__title-vert">{{ __("koba.story.{$key}_eyebrow") }}</span>
                        </div>

                        {{-- expanded content --}}
                        <div class="pmv-panel__body">
                            <div class="pmv-panel__media">
                                <img class="pmv-panel__img"
                                     src="/images/koba/{{ $meta['img'] }}-960.webp"
                                     alt="{{ __("koba.story.{$key}_eyebrow") }}"
                                     loading="lazy">
                            </div>
                            <div class="pmv-panel__content">
                                <span class="pmv-panel__num">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="pmv-panel__heading">{{ __("koba.story.{$key}_eyebrow") }}</h3>
                                <div class="pmv-panel__rule"></div>
                                <p class="pmv-panel__text">{{ __("koba.story.$key") }}</p>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- What We Stand For: the brand values as a scroll-driven track (resources/js/site/home.js → initPillarsSection) --}}
    @include('site.sections.pillars')

    {{-- Values --}}
    <section class="values section" aria-labelledby="values-title">
        <div class="wrap">
            <header class="grid values__head">
                <div class="values__heading">
                    <p class="eyebrow" data-reveal>{{ __('koba.story.values_eyebrow') }}</p>
                    <x-site.heading id="values-title" :text="__('koba.story.values_title')" size="l" />
                </div>
            </header>
            <ol class="list-reset values__list">
                @foreach (trans('koba.story.values') as $value)
                    <li class="value grid" data-reveal>
                        <span class="index value__index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="value__name display display--m">{{ $value['name'] }}</h3>
                        <p class="value__body body-copy">{{ $value['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @include('site.sections.philosophy')

    {{-- The Creator ----------------------------------------------------------------
         Files changed: resources/views/site/our-story.blade.php (this section only)
                        resources/css/site/pages.css  (.koba-creator-* block appended)
    ----------------------------------------------------------------------------- --}}
    <section
        class="koba-creator section is-white"
        aria-labelledby="creator-heading"
        data-creator
    >
        {{-- Oversized outlined wordmark, barely there, partly behind the image --}}
        <span class="koba-creator__ghost" aria-hidden="true">KOBA</span>

        {{-- Editorial index line: numbered marker ─── micro-typography --}}
        <div class="wrap koba-creator__top" data-creator-rule aria-hidden="true">
            <span class="koba-creator__no">01</span>
            <span class="koba-creator__top-rule"></span>
            <span class="koba-creator__tags">Craft&nbsp;/&nbsp;Discipline&nbsp;/&nbsp;Imagination</span>
        </div>

        <div class="wrap koba-creator__inner">

            {{-- ── IMAGE COLUMN (cols 1–7) ─────────────────────────────────── --}}
            <figure class="koba-creator__fig" data-creator-img>
                <div class="koba-creator__frame">
                    <img
                        src="{{ asset('images/koba/15.png') }}"
                        alt="A perfectly glazed KOBA éclair resting on a dark surface, its caramel coating flawlessly smooth and decorated with a single chocolate pearl — a study in precision pastry craft"
                        width="1100"
                        height="1500"
                        loading="lazy"
                        decoding="async"
                        class="koba-creator__img"
                    >
                </div>
                <figcaption class="koba-creator__caption" data-creator-caption>
                    Addis Ababa&nbsp;·&nbsp;Est.&nbsp;2020
                </figcaption>
            </figure>

            {{-- ── TEXT COLUMN (cols 8–12) ─────────────────────────────────── --}}
            <div class="koba-creator__copy">

                {{-- Eyebrow: line draws in, then text fades --}}
                <p class="koba-creator__eyebrow" data-creator-eyebrow aria-hidden="true">
                    <span class="koba-creator__eyebrow-line" aria-hidden="true"></span>
                    <span class="koba-creator__eyebrow-text">THE CREATOR</span>
                </p>

                {{-- Signature: KOBA as a brand mark, set apart from the sentence --}}
                <p class="koba-creator__line koba-creator__sig" aria-hidden="true" data-creator-line="0">
                    <span>KOBA</span>
                </p>

                {{-- Heading: lines in overflow-hidden wrappers for slide-up.
                     The accessible name keeps the full original sentence. --}}
                <h2
                    id="creator-heading"
                    class="koba-creator__heading"
                    aria-label="KOBA crafts with imagination, grounded in discipline."
                >
                    <span class="koba-creator__line" aria-hidden="true" data-creator-line="1">
                        <span>crafts with imagination,</span>
                    </span>
                    <span class="koba-creator__line koba-creator__line--quiet" aria-hidden="true" data-creator-line="2">
                        <span>grounded in discipline.</span>
                    </span>
                </h2>

                {{-- Body: three composed lines --}}
                <div class="koba-creator__body">
                    <p class="koba-creator__body-line" data-creator-body="0">Every flavor is composed.</p>
                    <p class="koba-creator__body-line" data-creator-body="1">Every texture is layered.</p>
                    <p class="koba-creator__body-line" data-creator-body="2">Every presentation is intentional.</p>
                </div>

                {{-- Closing statement: divider draws, then text fades --}}
                <div class="koba-creator__closing" data-creator-closing>
                    <div class="koba-creator__closing-rule" aria-hidden="true"></div>
                    <p class="koba-creator__closing-text">
                        Beauty is not decoration. It is discipline made visible&nbsp;—
                        <em class="koba-creator__closing-em">an art form you can taste and experience.</em>
                    </p>
                </div>

            </div>{{-- /.koba-creator__copy --}}
        </div>{{-- /.koba-creator__inner --}}

        {{-- Philosophy moment — the approved purpose statement (first sentence, verbatim from lang) --}}
        @php($purposeLine = \Illuminate\Support\Str::replaceLast('.', '.*', str_replace(['turn everyday', 'into meaningful'], ['turn|everyday', 'into|*meaningful'], \Illuminate\Support\Str::before(__('koba.story.purpose'), '. ').'.')))
        <div class="wrap koba-creator__statement">
            <p class="koba-creator__statement-eyebrow eyebrow" data-reveal>{{ __('koba.story.purpose_eyebrow') }}</p>
            <x-site.heading :text="$purposeLine" tag="p" size="l" class="koba-creator__statement-text" />
        </div>
    </section>

    {{-- Self-contained creator animation — runs once after JS class is set --}}
    <script>
    (function () {
        /* Guard: only run when JS is confirmed and reduced-motion is off */
        if (!document.documentElement.classList.contains('js')) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var section   = document.querySelector('[data-creator]');
        if (!section) return;

        var fig       = section.querySelector('[data-creator-img]');
        var eyebrow   = section.querySelector('[data-creator-eyebrow]');
        var lines     = section.querySelectorAll('[data-creator-line]');
        var bodyLines = section.querySelectorAll('[data-creator-body]');
        var closing   = section.querySelector('[data-creator-closing]');
        var caption   = section.querySelector('[data-creator-caption]');
        var rule      = section.querySelector('[data-creator-rule]');

        /* Lock elements in their start state — only after JS confirms presence */
        section.classList.add('koba-creator--js');

        var played = false;

        function play() {
            if (played) return;
            played = true;

            /* 0. Index line draws across the top */
            if (rule) {
                requestAnimationFrame(function () { rule.classList.add('is-revealed'); });
            }

            /* 1. Image reveal — CSS handles clip-path + scale via .is-revealed */
            if (fig) {
                requestAnimationFrame(function () { fig.classList.add('is-revealed'); });
            }

            /* 2. Eyebrow: line scaleX 0→1 at 400ms, text fades at 700ms */
            if (eyebrow) {
                setTimeout(function () { eyebrow.classList.add('is-revealed'); }, 400);
            }

            /* Caption fades with eyebrow */
            if (caption) {
                setTimeout(function () { caption.classList.add('is-revealed'); }, 500);
            }

            /* 3. Heading lines: stagger 120ms, starting at 700ms */
            lines.forEach(function (line, i) {
                setTimeout(function () { line.classList.add('is-revealed'); }, 700 + i * 120);
            });

            /* 4. Body lines: stagger 110ms, starting at 1050ms */
            bodyLines.forEach(function (p, i) {
                setTimeout(function () { p.classList.add('is-revealed'); }, 1050 + i * 110);
            });

            /* 5. Closing: rule draws at 1440ms, text fades at 1680ms */
            if (closing) {
                setTimeout(function () { closing.classList.add('is-revealed'); }, 1440);
            }
        }

        /* Trigger once at 25% section visibility */
        var observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                play();
                observer.disconnect();
            }
        }, { threshold: 0.25 });

        observer.observe(section);

        /* ── Scroll depth on the image ───────────────────────────────────
           All devices: scale eases 1.03 → 1.00 and the picture drifts a few px sideways.
           Desktop + fine pointer only: the figure also travels a little vertically. */
        if (fig) {
            var ticking  = false;
            var RANGE    = 24; /* max px of vertical travel */
            var parallax = window.matchMedia('(hover: hover) and (pointer: fine) and (min-width: 900px)').matches;

            function updateDepth() {
                ticking = false;
                var rect = fig.getBoundingClientRect();
                var vh   = window.innerHeight;
                if (rect.bottom < -200 || rect.top > vh + 200) return;
                var p = Math.min(1, Math.max(0, (vh - rect.top) / (vh + rect.height))); /* 0 entering → 1 leaving */
                fig.style.setProperty('--img-s', (1.03 - 0.03 * Math.min(1, p * 1.6)).toFixed(4));
                fig.style.setProperty('--img-x', ((0.5 - p) * 16).toFixed(1) + 'px');
                if (parallax) {
                    fig.style.setProperty('--parallax-y', (((rect.top + rect.height / 2) / vh - 0.5) * RANGE).toFixed(1) + 'px');
                }
            }

            window.addEventListener('scroll', function () {
                if (!ticking) { requestAnimationFrame(updateDepth); ticking = true; }
            }, { passive: true });
            updateDepth();
        }
    })();
    </script>

    @include('site.sections.final-cta')
</x-site.layout>

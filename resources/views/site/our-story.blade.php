<x-site.layout page="our-story" :title="__('koba.meta.pages.our-story')" :description="__('koba.story.lede')" image="craft-crumb">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.story.eyebrow'),
        'title' => __('koba.story.title'),
        'lede' => __('koba.story.lede'),
        'slides' => [['croissant', '50% 55%'], ['craft-almond', '50% 45%'], ['craft-crumb', '50% 50%'], ['opera-slice', '50% 60%']],
    ])

    {{-- Chapters --}}
    <section class="chapters section is-white" aria-label="{{ __('koba.story.eyebrow') }}">
        <div class="wrap">
            @foreach (trans('koba.story.chapters') as $i => $chapter)
                <article class="chapter grid {{ $i % 2 ? 'chapter--flip' : '' }} chapter--{{ $i }}">
                    <p class="chapter__no" data-reveal>{{ $chapter['no'] }}</p>
                    <div class="chapter__copy">
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
                                    width="1200"
                                    height="1500"
                                    loading="lazy"
                                    decoding="async"
                                    style="object-fit: contain; object-position: left center; background-color: #062e22;"
                                >
                            </div>
                            {{-- Soft gradient blending the dark-green image edge into the white text column --}}
                            <div class="chapter__media-blend" aria-hidden="true"></div>
                        </figure>
                    @elseif ($i === 1)
                        {{-- Chapter 1 uses custom image 12.png --}}
                        <figure class="chapter__media" data-parallax="-0.05">
                            <div class="media" data-reveal="media">
                                <img
                                    class="img"
                                    src="{{ asset('images/koba/12.png') }}"
                                    alt="Layers of a handcrafted KOBA pastry showing precision and craftsmanship"
                                    width="1200"
                                    height="1500"
                                    loading="lazy"
                                    decoding="async"
                                    style="object-fit: contain; object-position: center; background-color: #062e22;"
                                >
                            </div>
                        </figure>
                    @else
                        <figure class="chapter__media" data-parallax="{{ $i % 2 ? '-0.05' : '0.05' }}">
                            <div class="media" data-reveal="media">
                                <x-site.img :src="$chapter['image']" sizes="(min-width: 900px) 40vw, 90vw" />
                            </div>
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

    {{-- Purpose, mission, vision --}}
    <section class="statements section is-dark" aria-label="{{ __('koba.story.purpose_eyebrow') }}">
        <div class="wrap">
            @foreach (['purpose', 'mission', 'vision'] as $key)
                <div class="statement grid">
                    <p class="eyebrow statement__label" data-reveal>{{ __("koba.story.{$key}_eyebrow") }}</p>
                    <p class="statement__text" data-reveal style="--d: 1">{{ __("koba.story.$key") }}</p>
                </div>
            @endforeach
        </div>
    </section>

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

    {{-- The Creator ----------------------------------------------------------------
         Files changed: resources/views/site/our-story.blade.php (this section only)
                        resources/css/site/pages.css  (.koba-creator-* block appended)
    ----------------------------------------------------------------------------- --}}
    <section
        class="koba-creator section is-white"
        aria-labelledby="creator-heading"
        data-creator
    >
        <div class="wrap koba-creator__inner">

            {{-- ── IMAGE COLUMN (cols 1–7) ─────────────────────────────────── --}}
            <figure class="koba-creator__fig" data-creator-img>
                <div class="koba-creator__frame">
                    <img
                        src="{{ asset('images/koba/15.png') }}"
                        alt="A perfectly glazed KOBA éclair resting on a dark surface, its caramel coating flawlessly smooth and decorated with a single chocolate pearl — a study in precision pastry craft"
                        width="1200"
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

                {{-- Heading: two lines in overflow-hidden wrappers for slide-up --}}
                <h2
                    id="creator-heading"
                    class="koba-creator__heading"
                    aria-label="KOBA crafts with imagination, grounded in discipline."
                >
                    <span class="koba-creator__line" aria-hidden="true" data-creator-line="0">
                        <span>KOBA crafts with imagination,</span>
                    </span>
                    <span class="koba-creator__line koba-creator__line--quiet" aria-hidden="true" data-creator-line="1">
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

        /* Lock elements in their start state — only after JS confirms presence */
        section.classList.add('koba-creator--js');

        var played = false;

        function play() {
            if (played) return;
            played = true;

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

        /* ── Scroll parallax on the image (desktop + fine pointer only) ── */
        if (window.matchMedia('(hover: hover) and (pointer: fine) and (min-width: 900px)').matches && fig) {
            var ticking = false;
            var RANGE   = 24; /* max px of travel */

            function updateParallax() {
                ticking = false;
                var rect  = fig.getBoundingClientRect();
                var vh    = window.innerHeight;
                if (rect.bottom < -200 || rect.top > vh + 200) return;
                var offset = ((rect.top + rect.height / 2) / vh - 0.5) * RANGE;
                fig.style.setProperty('--parallax-y', offset.toFixed(1) + 'px');
            }

            window.addEventListener('scroll', function () {
                if (!ticking) { requestAnimationFrame(updateParallax); ticking = true; }
            }, { passive: true });
        }
    })();
    </script>

    @include('site.sections.final-cta')
</x-site.layout>

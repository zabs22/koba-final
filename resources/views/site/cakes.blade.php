@php
    $cakes = trans('koba.cakes');
    $page = trans('koba.cakes_page');
    // Cakes-page copy from Koba_Website Content.pdf, pages 6 and 7.
    // Keep the previous shared translations available for future use.
    $page['title'] = 'Signature|*Collection.*';
    $page['lede'] = 'Our Signature Collection brings together the creations our guests love most. From our famous cheesecakes, recognized among the finest Cheesecake in Addis Ababa, to rich Chocolate Cake, each masterpiece is made with premium, care and passion. Our collection also features artisan pastries prepared daily using time-honored baking techniques.';
    $largest = fn ($key) => asset("images/koba/$key-".last(config("koba.images.$key")[2]).'.webp');
@endphp

<x-site.layout page="cakes" :title="__('koba.meta.pages.cakes')" :description="$page['lede']" image="cake-opera-caramel">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.cakes_page.eyebrow'),
        'title' => $page['title'],
        'lede' => 'Handcrafted cakes for the moments worth celebrating.',
        'slides' => [['cake-opera-chocolate', '50% 58%'], ['cake-trio', '50% 58%'], ['cake-meringue', '50% 58%'], ['cake-chocolate-fudge', '50% 58%'], ['cake-red-velvet', '50% 58%']],
        'actions' => [
            [__('koba.cakes_page.cta'), route('order'), true],
            [__('koba.cakes_feature.cta_explore'), '#signature'],
        ],
    ])

    <div class="ck" data-cake-dialog>

        {{-- 01 · The Signature Five — 3D stage --}}
        <section class="ck-stage section is-deep" id="signature" aria-labelledby="signature-title" data-ck-stage style="--n: {{ count($cakes) }}">
            <div class="ck-stage__ghost" aria-hidden="true">
                @foreach ($cakes as $cake)
                    <span class="{{ $loop->first ? 'is-active' : '' }}" data-ck-ghost>{{ $cake['name'] }}</span>
                @endforeach
            </div>

            <div class="wrap grid ck-stage__grid">
                <div class="ck-stage__copy">
                    <p class="eyebrow" data-reveal>{{ $page['featured_eyebrow'] }}</p>
                    <x-site.heading id="signature-title" :text="$page['featured_title']" size="l" />

                    <div class="ck-stage__info" aria-live="polite">
                        @foreach ($cakes as $slug => $cake)
                            <div class="ck-info {{ $loop->first ? 'is-active' : '' }}" data-ck-info @unless ($loop->first) hidden @endunless>
                                <p class="ck-info__count"><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span> / {{ str_pad(count($cakes), 2, '0', STR_PAD_LEFT) }}</p>
                                <h3 class="ck-info__name display">{{ $cake['name'] }}</h3>
                                <p class="ck-info__line">{{ $cake['line'] }}</p>
                                <a class="btn btn--sand" href="{{ route('order', ['cake' => $slug]) }}">{{ $page['order_this'] }} <x-site.arrow /></a>
                            </div>
                        @endforeach
                    </div>

                    <div class="ck-stage__nav">
                        <button class="round-btn" type="button" data-ck-prev aria-label="{{ $page['stage_prev'] }}"><x-site.arrow class="flip" /></button>
                        <button class="round-btn" type="button" data-ck-next aria-label="{{ $page['stage_next'] }}"><x-site.arrow /></button>
                        <ol class="list-reset ck-thumbs">
                            @foreach ($cakes as $cake)
                                <li>
                                    <button class="ck-thumb {{ $loop->first ? 'is-active' : '' }}" type="button" data-ck-thumb aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('koba.cakes_page.stage_show', ['name' => $cake['name']]) }}">
                                        <x-site.img :src="$cake['image']" alt="" sizes="56px" />
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <div class="ck-stage__scene" data-ck-scene>
                    {{-- Rotating text circle hidden as requested.
                    <svg class="ck-ring" viewBox="0 0 400 400" aria-hidden="true">
                        <defs><path id="ck-ring-path" d="M200 200m-178 0a178 178 0 1 1 356 0a178 178 0 1 1-356 0"/></defs>
                        <circle cx="200" cy="200" r="196"/>
                        <text><textPath href="#ck-ring-path" textLength="1110" lengthAdjust="spacing">{{ str_repeat($page['stage_ring'], 2) }}</textPath></text>
                    </svg>
                    --}}
                    <div class="ck-stage__plinth" aria-hidden="true"></div>
                    <ul class="list-reset ck-carousel" data-ck-carousel>
                        @foreach ($cakes as $slug => $cake)
                            <li class="ck-slide" id="cake-{{ $slug }}" data-ck-slide style="--i: {{ $loop->index }}">
                                <a class="ck-slide__card" href="{{ route('order', ['cake' => $slug]) }}" aria-label="Order {{ $cake['name'] }}" draggable="false">
                                    <x-site.img :src="'cutout-'.$cake['image']" :alt="$cake['name']" sizes="(min-width: 900px) 30vw, 70vw" :eager="$loop->first" draggable="false" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="ck-stage__hint" aria-hidden="true">{{ $page['stage_hint'] }}</p>
                </div>
            </div>
        </section>

        {{-- 03 · Anatomy — an exploded 3D cake --}}
        {{-- Anatomy of a cake hidden as requested.
        <section class="ck-layers section is-white" aria-labelledby="layers-title" data-ck-layers>
            <div class="ck-layers__pin">
                <div class="wrap grid ck-layers__grid">
                    <div class="ck-layers__copy">
                        <p class="eyebrow" data-reveal>{{ $page['layers_eyebrow'] }}</p>
                        <x-site.heading id="layers-title" :text="$page['layers_title']" size="l" />
                        <p class="lede" data-reveal>{{ $page['layers_body'] }}</p>
                        <ol class="list-reset ck-legend">
                            @foreach ($page['layers'] as $layer)
                                <li class="ck-legend__item" data-ck-legend style="--k: {{ $loop->index }}">
                                    <span class="ck-legend__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="ck-legend__name">{{ $layer['name'] }}</span>
                                    <span class="ck-legend__line">{{ $layer['line'] }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="ck-iso" aria-hidden="true">
                        <div class="ck-iso__world">
                            @foreach (['glaze', 'cream', 'sponge', 'base'] as $layer)
                                <div class="ck-iso__layer ck-iso__layer--{{ $layer }}" style="--k: {{ $loop->index }}">
                                    <span class="ck-iso__top"></span>
                                    <span class="ck-iso__side"></span>
                                    @if ($layer === 'glaze')
                                        <span class="ck-drip ck-drip--front" data-ck-drip="front"></span>
                                        <span class="ck-drip ck-drip--left" data-ck-drip="left"></span>
                                    @endif
                                </div>
                            @endforeach
                            <div class="ck-pool" data-ck-pool></div>
                            <div class="ck-fall ck-fall--front" data-ck-fall="front"></div>
                            <div class="ck-fall ck-fall--left" data-ck-fall="left"></div>
                        </div>
                        <div class="ck-iso__shadow"></div>
                    </div>
                </div>
            </div>
        </section>
        --}}

        {{-- 04 · Gallery — tilt cards, opens the lightbox --}}
        {{-- Repeated cake gallery hidden to follow the supplied content.
        <section class="ck-gallery section is-cream-2 has-trees"aria-labelledby="gallery-title">
            <div class="wrap">
                <header class="grid ck-head">
                    <div class="ck-head__title">
                        <p class="eyebrow" data-reveal>{{ $page['gallery_eyebrow'] }}</p>
                        <x-site.heading id="gallery-title" :text="$page['gallery_title']" size="l" />
                    </div>
                </header>

                <ul class="list-reset ck-gallery__grid">
                    @foreach (trans('koba.gallery_cakes') as $item)
                        <li class="ck-gallery__item ck-gallery__item--{{ $loop->iteration }}" data-reveal style="--d: {{ $loop->index % 3 }}">
                            <button class="ck-tile" type="button" data-lightbox data-ck-tilt data-full="{{ $largest($item['image']) }}" data-alt="{{ $item['alt'] }}" data-cursor="{{ __('koba.ui.view') }}">
                                <x-site.img :src="$item['image']" :alt="$item['alt']" sizes="(min-width: 900px) 30vw, 45vw" position="50% 62%" />
                                <span class="ck-tile__glare" aria-hidden="true"></span>
                                <span class="ck-tile__caption" aria-hidden="true">{{ $item['alt'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <dialog class="lightbox" data-lightbox-dialog aria-label="{{ $page['gallery_eyebrow'] }}">
                <figure class="lightbox__figure">
                    <img class="lightbox__img" src="" alt="" data-lightbox-img>
                    <figcaption class="lightbox__caption" data-lightbox-caption></figcaption>
                </figure>
                <div class="lightbox__controls">
                    <button class="round-btn" type="button" data-lightbox-prev aria-label="{{ __('koba.ui.previous') }}"><x-site.arrow class="flip" /></button>
                    <span class="index" data-lightbox-count></span>
                    <button class="round-btn" type="button" data-lightbox-next aria-label="{{ __('koba.ui.next') }}"><x-site.arrow /></button>
                </div>
                <button class="lightbox__close link" type="button" data-lightbox-close>{{ $page['close'] }}</button>
            </dialog>
        </section>
        --}}

        {{-- 05 · By the slice — 3D rail --}}
        {{-- Repeated slice showcase hidden to follow the supplied content.
        <section class="ck-slices section is-white" aria-labelledby="slices-title">
            <div class="wrap">
                <header class="grid ck-head">
                    <div class="ck-head__title">
                        <p class="eyebrow" data-reveal>{{ $page['slices_eyebrow'] }}</p>
                        <x-site.heading id="slices-title" :text="$page['slices_title']" size="l" />
                    </div>
                    <p class="ck-head__hint" aria-hidden="true"><span></span>{{ $page['slices_hint'] }}</p>
                </header>
            </div>
            <ul class="list-reset ck-rail" data-ck-rail tabindex="0" aria-label="{{ $page['slices_eyebrow'] }}">
                @foreach (trans('koba.slices') as $slice)
                    <li class="ck-rail__item" data-ck-rail-item>
                        <figure class="ck-rail__card">
                            <div class="ck-rail__media">
                                <x-site.img :src="$slice['image']" :alt="$slice['alt']" sizes="(min-width: 900px) 26vw, 70vw" />
                            </div>
                            <figcaption><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $slice['alt'] }}</figcaption>
                        </figure>
                    </li>
                @endforeach
            </ul>
        </section>
        --}}

        {{-- Seasonal collection: Koba_Website Content.pdf, page 7. --}}
        {{-- Previous text-only seasonal section, preserved for reference.
        <section class="section is-cream-2" aria-labelledby="seasonal-title">
            <div class="wrap grid ck-head">
                <div class="ck-head__title">
                    <p class="eyebrow" data-reveal>For special occasions</p>
                    <x-site.heading id="seasonal-title" text="Seasonal|*Collection.*" size="l" />
                </div>
                <p class="lede cakes-seasonal__copy" data-reveal>Throughout the year, we introduce elegant seasonal selections designed to celebrate holidays and special occasions, making KOBA one of the best bakeries for celebrations in Addis Ababa, where every milestone is made sweeter.</p>
            </div>
        </section>
        --}}
        {{-- Image-card seasonal variation preserved, hidden in favor of the original copy.
        <section class="cakes-seasonal section is-cream-2" aria-labelledby="seasonal-title">
            <div class="wrap">
                <header class="cakes-seasonal__head">
                    <div>
                        <p class="eyebrow" data-reveal>For special occasions</p>
                        <x-site.heading id="seasonal-title" text="Seasonal|*Collection.*" size="l" />
                    </div>
                    <div class="cakes-seasonal__intro" data-reveal>
                        <p class="lede">Elegant selections for holidays, celebrations, and every sweet milestone.</p>
                        <p class="cakes-seasonal__hint">Open a cake to explore your next celebration.</p>
                    </div>
                </header>
                <div class="cakes-seasonal__grid">
                    @foreach ([
                        ['image' => 'cake-meringue', 'title' => 'Make it a celebration', 'line' => 'For birthdays, anniversaries, and the milestones you share.'],
                        ['image' => 'cake-opera-chocolate', 'title' => 'Gather for something special', 'line' => 'Bring something sweet to the table for holidays and special occasions.'],
                        ['image' => 'cake-red-velvet', 'title' => 'A thoughtful surprise', 'line' => 'Make a small gesture feel like a special occasion.'],
                    ] as $occasion)
                        <details class="cakes-seasonal__card" data-reveal style="--d: {{ $loop->index }}">
                            <summary>
                                <span class="cakes-seasonal__photo">
                                    <x-site.img :src="$occasion['image']" sizes="(min-width: 900px) 30vw, (min-width: 600px) 45vw, 90vw" />
                                    <span class="cakes-seasonal__number" aria-hidden="true">0{{ $loop->iteration }}</span>
                                </span>
                                <span class="cakes-seasonal__caption">
                                    <span>{{ $occasion['title'] }}</span>
                                    <span class="cakes-seasonal__toggle" aria-hidden="true">+</span>
                                </span>
                            </summary>
                            <div class="cakes-seasonal__details">
                                <p>{{ $occasion['line'] }}</p>
                                <a class="link" href="{{ route('order') }}">Plan your cake <x-site.arrow /></a>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
        --}}
        <section class="seasonal-note section is-cream-2" aria-labelledby="seasonal-title">
            <x-site.img src="cake-chocolate-fudge" class="seasonal-note__background" alt="" aria-hidden="true" sizes="(min-width: 900px) 65vw, 100vw" />
            <div class="wrap seasonal-note__frame">
                <header class="seasonal-note__heading">
                    <p class="eyebrow" data-reveal>For special occasions</p>
                    <x-site.heading id="seasonal-title" text="Seasonal|*Collection.*" size="l" />
                </header>
                <div class="seasonal-note__copy" data-reveal>
                    <p>Throughout the year, we introduce elegant seasonal selections designed to celebrate holidays and special occasions, making KOBA one of the best bakeries for celebrations in Addis Ababa, <span class="seasonal-note__finish">where every milestone is made sweeter.</span></p>
                </div>
            </div>
        </section>

        {{-- 06 · How ordering works --}}
        <section class="ck-order section is-sand has-trees" aria-label="Order your cake" data-ck-order>
            <div class="wrap">
                {{-- Additional ordering-process copy hidden to follow the supplied content.
                <h2 id="steps-title" class="eyebrow" data-reveal>{{ $page['steps_eyebrow'] }}</h2>
                <div class="ck-steps-wrap">
                <svg class="ck-steps__line" viewBox="0 0 1000 20" preserveAspectRatio="none" aria-hidden="true"><path d="M0 10H1000" pathLength="1"/></svg>
                <ol class="list-reset ck-steps">
                    @foreach ($page['steps'] as $step)
                        <li class="ck-steps__item" data-reveal style="--d: {{ $loop->index }}">
                            <span class="ck-steps__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="display display--s">{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
                </div>

                --}}

                <div class="ck-cta" data-reveal>
                    <div class="ck-cta__copy">
                        <p class="display display--m">{{ $page['cta_title'] }}</p>
                        <p class="lede">{{ $page['cta_body'] }}</p>
                        <a class="btn btn--sand" href="{{ route('order') }}">{{ $page['cta'] }} <x-site.arrow /></a>
                    </div>
                    <div class="ck-cta__media" aria-hidden="true">
                        <x-site.img src="cake-red-velvet" alt="" sizes="(min-width: 900px) 30vw, 80vw" position="50% 62%" />
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-site.layout>

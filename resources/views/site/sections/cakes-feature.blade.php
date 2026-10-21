{{-- Signature cakes on a 3D ring. The five cakes are set twice so the ring reads
     as a full cylinder. Behaviour: resources/js/site/home.js → initRing --}}
@php
    $cakes = collect(trans('koba.cakes'))->map(fn ($cake, $slug) => $cake + ['slug' => $slug])->values();
    $faces = $cakes->concat($cakes);
@endphp

<section class="cakes-feature section is-sand" aria-labelledby="cakes-feature-title" data-chapter="{{ __('koba.chapters.cakes') }}">
    <x-site.leaf class="cakes-feature__leaf" data-parallax="0.1" />

    <div class="wrap">
        <header class="grid cakes-feature__head">
            <div class="cakes-feature__heading">
                <p class="eyebrow" data-reveal>{{ __('koba.cakes_feature.eyebrow') }}</p>
                <x-site.heading id="cakes-feature-title" :text="__('koba.cakes_feature.title')" size="l" />
            </div>
            <div class="cakes-feature__intro">
                <p class="lede" data-reveal>Cakes made for every celebration.</p>
                <div class="btn-row" data-reveal style="--d: 1">
                    <a class="btn" href="{{ route('order') }}">{{ __('koba.cakes_feature.cta_order') }} <x-site.arrow /></a>
                    <a class="link" href="{{ route('cakes') }}">{{ __('koba.cakes_feature.cta_explore') }}</a>
                </div>
            </div>
        </header>
    </div>

    <div class="ring" data-ring style="--n: {{ $faces->count() }}">
        <p class="ring__backdrop" aria-hidden="true">{{ __('koba.cakes_feature.backdrop') }}</p>

        <div class="ring__scene" data-ring-scene>
            <div class="ring__floor" aria-hidden="true"></div>
            <ul class="list-reset ring__spin" data-ring-spin aria-label="{{ __('koba.cakes_feature.ring_label') }}">
                @foreach ($faces as $i => $cake)
                    @php($copy = $i >= $cakes->count())
                    <li class="ring__face" data-ring-face data-index="{{ $i % $cakes->count() }}" style="--i: {{ $i }}" @if ($copy) aria-hidden="true" @endif>
                        <a class="ring__card" href="{{ route('cakes') }}#cake-{{ $cake['slug'] }}" draggable="false" data-cursor="{{ __('koba.ui.discover') }}" @if ($copy) tabindex="-1" @endif>
                            <x-site.img :src="$cake['image']" :alt="$cake['name']" sizes="(min-width: 900px) 22vw, 52vw" position="50% 62%" draggable="false" />
                            <span class="ring__shine" aria-hidden="true"></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="wrap ring__info">
            <button class="round-btn" type="button" data-ring-prev aria-label="{{ __('koba.ui.previous') }}"><x-site.arrow class="flip" /></button>

            <div class="ring__captions">
                @foreach ($cakes as $i => $cake)
                    <div class="ring__caption {{ $i === 0 ? 'is-active' : '' }}" data-ring-caption>
                        <span class="index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($cakes->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="display display--m">{{ $cake['name'] }}</h3>
                        <p>{{ $cake['line'] }}</p>
                        <a class="link" href="{{ route('order', ['cake' => $cake['slug']]) }}" @unless ($i === 0) tabindex="-1" @endunless>{{ __('koba.cakes_feature.order_this') }} <x-site.arrow /></a>
                    </div>
                @endforeach
            </div>

            <button class="round-btn" type="button" data-ring-next aria-label="{{ __('koba.ui.next') }}"><x-site.arrow /></button>
        </div>
        <p class="ring__hint" aria-hidden="true">{{ __('koba.cakes_feature.drag') }}</p>
    </div>
</section>

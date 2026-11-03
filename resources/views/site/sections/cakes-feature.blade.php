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
        {{-- <p class="ring__backdrop" aria-hidden="true">{{ __('koba.cakes_feature.backdrop') }}</p> --}}

        <div class="ring__scene" data-ring-scene>
            <div class="ring__floor" aria-hidden="true"></div>
            <ul class="list-reset ring__spin" data-ring-spin aria-label="{{ __('koba.cakes_feature.ring_label') }}">
                @foreach ($faces as $i => $cake)
                    @php($copy = $i >= $cakes->count())
                    <li class="ring__face" data-ring-face data-index="{{ $i % $cakes->count() }}" style="--i: {{ $i }}" @if ($copy) aria-hidden="true" @endif>
                        <a class="ring__card" href="{{ route('cakes') }}#cake-{{ $cake['slug'] }}" draggable="false" @if ($copy) tabindex="-1" @endif>
                            <span class="ring__shadow" aria-hidden="true"></span>
                            <x-site.img :src="'cutout-'.$cake['image']" :alt="$cake['name']" sizes="(min-width: 900px) 22vw, 52vw" draggable="false" />
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
                        <button class="link" type="button" data-cart-add data-cake data-label-added="{{ __('koba.cart.added') }}"
                                data-slug="{{ $cake['slug'] }}" data-name="{{ $cake['name'] }}" data-title="{{ $cake['name'] }}"
                                data-image="{{ asset('images/koba/'.$cake['image'].'-'.config('koba.images.'.$cake['image'])[2][0].'.webp') }}"
                                aria-label="{{ __('koba.cart.add') }}: {{ $cake['name'] }}" @unless ($i === 0) tabindex="-1" @endunless>
                            <span data-cart-add-label>{{ __('koba.cart.add') }}</span> <x-site.arrow />
                        </button>
                    </div>
                @endforeach
            </div>

            <button class="round-btn" type="button" data-ring-next aria-label="{{ __('koba.ui.next') }}"><x-site.arrow /></button>
        </div>
        <p class="ring__hint" aria-hidden="true">{{ __('koba.cakes_feature.drag') }}</p>
    </div>
</section>

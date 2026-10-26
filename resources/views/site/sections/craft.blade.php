@php
    // Balance the masonry: place each photograph in the currently shortest
    // column, using its real proportions, so the three columns end level.
    $items = trans('koba.craft.items');
    $columns = [[], [], []];
    $heights = [0, 0, 0];
    foreach ($items as $i => $item) {
        [$w, $h] = config('koba.images.'.$item['image']);
        $shortest = array_search(min($heights), $heights);
        $columns[$shortest][] = $i;
        $heights[$shortest] += $h / $w;
    }
    $largest = fn ($key) => asset("images/koba/$key-".last(config("koba.images.$key")[2]).'.webp');
@endphp

<section class="craft section is-white has-trees"aria-labelledby="craft-title" data-craft data-chapter="{{ __('koba.chapters.craft') }}">
    <div class="wrap">
        <header class="grid craft__head">
            <div class="craft__heading">
                <p class="eyebrow" data-reveal>{{ __('koba.craft.eyebrow') }}</p>
                <x-site.heading id="craft-title" :text="__('koba.craft.title')" size="l" />
            </div>
            <div class="craft__aside" data-reveal>
                <p class="lede craft__lede">Folded by hand. Baked in delicate layers.</p>
                <p class="craft__hint">{{ __('koba.craft.hint') }}</p>
            </div>
        </header>

        {{-- Masonry. The columns glide in from opposite directions and drift on scroll
             (resources/js/site/home.js → initCraft); each photograph opens full size on click. --}}
        <div class="craft__masonry" data-craft-wall>
            @foreach ($columns as $c => $indexes)
                <ol class="craft__col list-reset" style="--c: {{ $c }}">
                    @foreach ($indexes as $i)
                        @php($item = $items[$i])
                        <li class="craft__tile" data-craft-tile style="--r: {{ $c + $loop->index * 2 }}">
                            <button class="craft__figure media" type="button" data-craft-open="{{ $i }}"
                                    data-full="{{ $largest($item['image']) }}" data-title="{{ $item['title'] }}" data-line="{{ $item['line'] }}"
                                    aria-label="{{ $item['title'] }}: {{ $item['line'] }}">
                                <x-site.img :src="$item['image']" sizes="(min-width: 1100px) 31vw, (min-width: 600px) 46vw, 46vw" />
                                <span class="craft__caption" aria-hidden="true">
                                    <span class="index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <strong>{{ $item['title'] }}</strong>
                                    <span class="craft__line">{{ $item['line'] }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>
    </div>

    <dialog class="craft-view" data-craft-view aria-label="{{ __('koba.craft.eyebrow') }}">
        <figure class="craft-view__figure">
            <img class="craft-view__img" src="" alt="" data-craft-view-img>
        </figure>
        <div class="craft-view__text">
            <p class="craft-view__no index" data-craft-view-no></p>
            <h3 class="craft-view__title" data-craft-view-title></h3>
            <p class="craft-view__line" data-craft-view-line></p>
            <div class="craft-view__nav">
                <button class="round-btn" type="button" data-craft-view-prev aria-label="{{ __('koba.ui.previous') }}"><x-site.arrow class="flip" /></button>
                <button class="round-btn" type="button" data-craft-view-next aria-label="{{ __('koba.ui.next') }}"><x-site.arrow /></button>
            </div>
        </div>
        <button class="craft-view__close" type="button" data-craft-view-close aria-label="{{ __('koba.ui.close') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
    </dialog>
</section>

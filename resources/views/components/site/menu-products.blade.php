@props(['products', 'idPrefix' => 'dish-', 'reveal' => false, 'category' => null])

@php
    $cakeSlugs = array_keys(trans('koba.cakes'));
@endphp

<ul class="list-reset menu-products">
    @foreach ($products as $product)
        @php
            // House name, composition and brand line for this item, when the kitchen has given one.
            $note = trans('koba.menu_notes.'.$product['slug']);
            $note = is_array($note) ? $note : [];
            $title = $note['title'] ?? $product['name'];
            $line = $note['line'] ?? $product['line'] ?? null;
            $image = !empty($product['image']) ? asset('images/koba/'.$product['image'].'-'.config('koba.images.'.$product['image'])[2][0].'.webp') : '';
        @endphp
        <li class="menu-product" id="{{ $idPrefix }}{{ $product['slug'] }}" data-menu-item data-name="{{ $title }} {{ $product['name'] }}"
            @if ($reveal) data-reveal style="--d: {{ $loop->index % 3 }}" @endif>
            @if (!empty($product['image']))
                <div class="menu-product__media">
                    <x-site.img :src="$product['image']" sizes="(min-width: 1000px) 30vw, (min-width: 600px) 45vw, 92vw" />
                    <span class="menu-product__shade" aria-hidden="true"></span>
                </div>
            @else
                {{-- No photograph yet: a green panel with the section's icon. --}}
                <div class="menu-product__media menu-product__media--empty" aria-hidden="true">
                    <x-site.menu-icon :name="$category" class="menu-product__icon" />
                </div>
            @endif
            <p class="menu-product__kind">
                {{ $product['name'] }}@if (!empty($note['kind']) && $title !== $product['name'])<span aria-hidden="true"> · </span>{{ $note['kind'] }}@endif
            </p>
            <div class="menu-product__details">
                <h3 class="menu-product__name">
                    @if (!empty($product['href']))
                        <a class="menu-product__link" href="{{ $product['href'] }}">{{ $title }}</a>
                    @else
                        {{ $title }}
                    @endif
                </h3>
                @if (isset($product['price'])) <p class="menu-product__price">{{ $product['price'] }}</p> @endif
            </div>
            @if (!empty($note['notes']))
                <ul class="list-reset menu-product__notes" aria-label="Composition">
                    @foreach ($note['notes'] as $part)<li>{{ $part }}</li>@endforeach
                </ul>
            @endif
            @if ($line) <p class="menu-product__description">{{ $line }}</p> @endif
            @if (($product['vegan'] ?? false) === true) <span class="menu-product__vegan">Vegan</span> @endif
            <button class="menu-product__add btn btn--ghost" type="button" data-cart-add data-label-added="{{ __('koba.cart.added') }}"
                    data-slug="{{ $product['slug'] }}" data-name="{{ $product['name'] }}" data-title="{{ $title }}" data-image="{{ $image }}"
                    @if (in_array($product['slug'], $cakeSlugs, true)) data-cake @endif
                    aria-label="{{ __('koba.cart.add') }}: {{ $title }}">
                <span class="menu-product__add-label" data-cart-add-label>{{ __('koba.cart.add') }}</span>
                {{-- A little mesob whose lid tips open --}}
                <svg class="menu-product__mesob" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round">
                    <path class="menu-product__mesob-lid" d="M12 2.5c.9 0 1.3 3 2.4 5 1.2 2.1 3.6 3.1 6.6 3.5H3c3-.4 5.4-1.4 6.6-3.5 1.1-2 1.5-5 2.4-5Z"/>
                    <path d="M4.2 13h15.6c-.5 2.5-2.6 3.9-4.9 4.3l.9 4.2H8.2l.9-4.2C6.8 16.9 4.7 15.5 4.2 13Z"/>
                </svg>
            </button>
        </li>
    @endforeach
</ul>

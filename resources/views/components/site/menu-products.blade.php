@props(['products', 'idPrefix' => 'dish-', 'reveal' => false])

<ul class="list-reset menu-products">
    @foreach ($products as $product)
        <li class="menu-product" id="{{ $idPrefix }}{{ $product['slug'] }}" data-menu-item data-name="{{ $product['name'] }}"
            @if ($reveal) data-reveal style="--d: {{ $loop->index % 3 }}" @endif>
            @if (!empty($product['image']))
                <div class="menu-product__media">
                    <x-site.img :src="$product['image']" sizes="(min-width: 1000px) 30vw, (min-width: 600px) 45vw, 92vw" />
                    <span class="menu-product__shade" aria-hidden="true"></span>
                    <a class="menu-product__order btn btn--sand" href="{{ route('order') }}" aria-label="Order {{ $product['name'] }} now">Order now <x-site.arrow /></a>
                </div>
            @endif
            <div class="menu-product__details">
                <h3 class="menu-product__name">
                    @if (!empty($product['href']))
                        <a class="menu-product__link" href="{{ $product['href'] }}">{{ $product['name'] }}</a>
                    @else
                        {{ $product['name'] }}
                    @endif
                </h3>
                @if (isset($product['price'])) <p class="menu-product__price">{{ $product['price'] }}</p> @endif
            </div>
            @if (!empty($product['line'])) <p class="menu-product__description">{{ $product['line'] }}</p> @endif
            @if (($product['vegan'] ?? false) === true) <span class="menu-product__vegan">Vegan</span> @endif
            @if (empty($product['image']))
                <a class="menu-product__order menu-product__order--inline btn btn--ghost" href="{{ route('order') }}" aria-label="Order {{ $product['name'] }} now">Order now <x-site.arrow /></a>
            @endif
        </li>
    @endforeach
</ul>

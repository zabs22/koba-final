@php
    $navItems = ['home', 'our-story', 'menu', 'cakes', 'experience', 'locations', 'contact'];
    $current = fn ($route) => request()->routeIs($route) ? 'page' : null;
@endphp

<header class="header" data-header>
    <div class="wrap header__inner">
        <a class="header__logo" href="{{ route('home') }}" aria-label="KOBA Patisserie and Bakery, {{ __('koba.nav.home') }}">
            <x-site.logo />
        </a>

        <nav class="nav" aria-label="Primary">
            <ul class="nav__list list-reset">
                @foreach ($navItems as $item)
                    <li>
                        <a class="nav__link" href="{{ route($item) }}" @if ($current($item)) aria-current="page" @endif>{{ __("koba.nav.$item") }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <a class="btn header__cta" href="{{ route('order') }}" @if ($current('order')) aria-current="page" @endif>
            {{ __('koba.ui.order_cake') }}
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="overlay-menu" data-menu-toggle
                data-label-open="{{ __('koba.ui.menu_open') }}" data-label-close="{{ __('koba.ui.menu_close') }}" data-close-text="{{ __('koba.ui.close') }}">
            <span class="menu-toggle__text" data-menu-label aria-hidden="true">{{ __('koba.ui.menu') }}</span>
            <span class="menu-toggle__bars" aria-hidden="true"></span>
            <span class="sr-only" data-menu-sr>{{ __('koba.ui.menu_open') }}</span>
        </button>
    </div>
</header>

<div class="overlay-menu is-deep" id="overlay-menu" data-overlay-menu aria-label="Menu" role="dialog" aria-modal="true">
    <ul class="overlay-menu__list list-reset">
        @foreach ([...$navItems, 'order'] as $i => $item)
            <li class="overlay-menu__item" style="--i: {{ $i }}">
                <a class="overlay-menu__link" href="{{ route($item) }}" @if ($current($item)) aria-current="page" @endif>
                    <span class="index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    {{ $item === 'order' ? __('koba.ui.order_cake') : __("koba.nav.$item") }}
                </a>
            </li>
        @endforeach
    </ul>
    <div class="overlay-menu__foot">
        <a class="u-link" href="mailto:{{ config('koba.email') }}">{{ config('koba.email') }}</a>
        <span>Sanford · Bole Atlas · 4 Killo</span>
    </div>
</div>

{{-- Cart: kept in the browser (resources/js/site/cart.js) and handed to the order form. --}}
<div class="cart" id="cart" data-cart hidden>
    <div class="cart__scrim" data-cart-close></div>
    <aside class="cart__panel is-deep" role="dialog" aria-modal="true" aria-labelledby="cart-title" tabindex="-1">
        <header class="cart__head">
            <h2 id="cart-title" class="cart__title">{{ __('koba.cart.title') }}</h2>
            <button class="cart__close" type="button" data-cart-close aria-label="{{ __('koba.cart.close') }}">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </button>
        </header>

        <ul class="list-reset cart__list" data-cart-list
            data-label-less="{{ __('koba.cart.less') }}" data-label-more="{{ __('koba.cart.more') }}" data-label-remove="{{ __('koba.cart.remove') }}"></ul>

        <div class="cart__empty" data-cart-empty>
            <p>{{ __('koba.cart.empty') }}</p>
            <a class="btn btn--sand" href="{{ route('menu') }}">{{ __('koba.cart.browse') }} <x-site.arrow /></a>
        </div>

        <footer class="cart__foot" data-cart-foot>
            <p class="cart__note">{{ __('koba.cart.note') }}</p>
            <a class="btn btn--sand cart__checkout" href="{{ route('order') }}" data-cart-checkout>{{ __('koba.cart.checkout') }} <x-site.arrow /></a>
        </footer>
    </aside>
</div>

{{-- The mesob: the cart as a woven basket, docked bottom-right (drawn and animated by mesob.js). --}}
<button class="mesob" type="button" data-mesob data-cart-open aria-controls="cart" aria-expanded="false" aria-label="{{ __('koba.cart.open') }}" hidden>
    <span class="mesob__float">
        <span class="mesob__shadow" aria-hidden="true"></span>
        <span class="mesob__tilt">
            <svg class="mesob__art" viewBox="0 0 160 200" aria-hidden="true" data-mesob-art></svg>
            <span class="mesob__count" data-mesob-count aria-hidden="true" hidden>0</span>
        </span>
    </span>
    <span class="mesob__label" aria-hidden="true">{{ __('koba.cart.title') }}</span>
</button>

<div class="mesob-toast" role="status" data-mesob-toast>
    <img class="mesob-toast__img" alt="" data-mesob-toast-img hidden>
    <span class="mesob-toast__text">
        <span class="mesob-toast__kicker">{{ __('koba.cart.mesob_added') }}</span>
        <span class="mesob-toast__title" data-mesob-toast-title></span>
    </span>
</div>

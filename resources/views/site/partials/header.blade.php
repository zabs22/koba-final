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

        {{-- Account: sign in (Jetstream) or straight to the signed-in user's dashboard. --}}
        <a class="header__account" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c1.2-3.6 4.2-5.5 7.5-5.5s6.3 1.9 7.5 5.5"/>
            </svg>
            <span class="header__account-label">{{ auth()->check() ? __('koba.ui.account') : __('koba.ui.sign_in') }}</span>
        </a>

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
        <a class="u-link" href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? __('koba.ui.account') : __('koba.ui.sign_in') }}</a>
        <a class="u-link" href="mailto:{{ config('koba.email') }}">{{ config('koba.email') }}</a>
        <span>{{ collect(trans('koba.locations.branches'))->pluck('short')->implode(' · ') }}</span>
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

{{-- The mesob: the cart as a woven basket, docked bottom-right (drawn and animated by mesob.js).
     Set aside for the cake box below.
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
--}}

{{-- The cake box: the cart as a KOBA cake box, docked bottom-right (animated by cakebox.js).
     Drawn from above at an angle: the lid (top + skirt) hinges at its back edge and opens when
     something is added; the item drops into the dark mouth beneath it. --}}
<button class="cakebox" type="button" data-cakebox data-cart-open aria-controls="cart" aria-expanded="false" aria-label="{{ __('koba.cart.open') }}" hidden>
    <span class="cakebox__float">
        <span class="cakebox__shadow" aria-hidden="true"></span>
        <span class="cakebox__tilt">
            <svg class="cakebox__art" viewBox="0 0 160 180" aria-hidden="true" data-cakebox-art>
                <defs>
                    <linearGradient id="cb-left" x1="0" x2="1">
                        <stop offset="0" stop-color="#f3e2c0"/>
                        <stop offset="1" stop-color="#fff7e8"/>
                    </linearGradient>
                    <linearGradient id="cb-right" x1="0" x2="1">
                        <stop offset="0" stop-color="#e9d2a6"/>
                        <stop offset="1" stop-color="#d9bb85"/>
                    </linearGradient>
                    <linearGradient id="cb-top" x1="0" x2="1" y1="0" y2="1">
                        <stop offset="0" stop-color="#fffaf0"/>
                        <stop offset="1" stop-color="#f4e4c4"/>
                    </linearGradient>
                    <radialGradient id="cb-inside" cx=".5" cy=".6" r=".6">
                        <stop offset="0" stop-color="#5c3814"/>
                        <stop offset="1" stop-color="#1e1007"/>
                    </radialGradient>
                    <radialGradient id="cb-light" cx=".5" cy=".5" r=".5">
                        <stop offset="0" stop-color="#ffd98f" stop-opacity=".9"/>
                        <stop offset="1" stop-color="#ffd98f" stop-opacity="0"/>
                    </radialGradient>
                    {{-- Everything above the box's front edges: an item sinking in disappears behind them. --}}
                    <clipPath id="cb-mouth"><path d="M0 -80H160V74H140L80 104L20 74H0Z"/></clipPath>
                </defs>

                {{-- The open mouth, seen when the lid lifts --}}
                <path d="M80 44L140 74L80 104L20 74Z" fill="url(#cb-inside)"/>
                <ellipse class="cb-glow" cx="80" cy="76" rx="38" ry="16" fill="url(#cb-light)"/>
                <g clip-path="url(#cb-mouth)" data-cakebox-mouth></g>

                {{-- The box: left and right faces, with the KOBA name and a gold foot --}}
                <path d="M20 74L80 104V170L20 140Z" fill="url(#cb-left)"/>
                <path d="M80 104L140 74V140L80 170Z" fill="url(#cb-right)"/>
                <path d="M20 134L80 164V170L20 140Z" fill="#e6be7e"/>
                <path d="M80 164L140 134V140L80 170Z" fill="#c9a161"/>
                <text transform="matrix(1 .5 0 1 0 0)" x="50" y="107" text-anchor="middle" font-family="Montserrat, sans-serif" font-size="13.5" font-weight="800" letter-spacing="1.2" fill="#062e22">KOBA</text>
                <text transform="matrix(1 .5 0 1 0 0)" x="50" y="116" text-anchor="middle" font-family="Montserrat, sans-serif" font-size="3.6" font-weight="700" letter-spacing="1" fill="#7a4e22">PATISSERIE &amp; BAKERY</text>
                <path d="M110 89V155" stroke="#0f5a3f" stroke-width="7" opacity=".9"/>
                <path d="M80 104V170" stroke="#fffaf0" stroke-opacity=".5" stroke-width=".8"/>

                {{-- The lid: top with a ribbon cross and bow, and a short skirt over the faces --}}
                <g class="cb-lid">
                    <path d="M17 72.5L80 104V114L17 82.5Z" fill="#fff4dd"/>
                    <path d="M80 104L143 72.5V82.5L80 114Z" fill="#e2c891"/>
                    <path d="M17 82.5L80 114L143 82.5" fill="none" stroke="#e6be7e" stroke-width="1.6"/>
                    <path d="M80 41L143 72.5L80 104L17 72.5Z" fill="url(#cb-top)"/>
                    {{-- Ribbon: across the top both ways, and down the skirt --}}
                    <path d="M45.5 57.75L52.5 54.25L114.5 85.25L107.5 88.75Z" fill="#0f5a3f"/>
                    <path d="M107.5 54.25L114.5 57.75L52.5 88.75L45.5 85.25Z" fill="#0b4a36"/>
                    <path d="M107.5 88.75L114.5 85.25V95.25L107.5 98.75Z" fill="#0f5a3f"/>
                    {{-- Bow --}}
                    <path d="M80 72C70 60 58 62 62 70C65 76 74 74 80 72Z" fill="#0f5a3f"/>
                    <path d="M80 72C90 60 102 62 98 70C95 76 86 74 80 72Z" fill="#0b4a36"/>
                    <path d="M80 72L72 84L76 85L80 75L84 85L88 84Z" fill="#0b4a36"/>
                    <ellipse cx="80" cy="72" rx="4.2" ry="3.2" fill="#13704f"/>
                    <path d="M64 67C66 64 70 64 73 67" fill="none" stroke="#fffaf0" stroke-opacity=".35" stroke-width="1.2" stroke-linecap="round"/>
                </g>
                <g data-cakebox-sparks></g>
            </svg>
            <span class="cakebox__count" data-cakebox-count aria-hidden="true" hidden>0</span>
        </span>
    </span>
    <span class="cakebox__label" aria-hidden="true">{{ __('koba.cart.title') }}</span>
</button>

<div class="mesob-toast" role="status" data-mesob-toast>
    <img class="mesob-toast__img" alt="" data-mesob-toast-img hidden>
    <span class="mesob-toast__text">
        <span class="mesob-toast__kicker">{{ __('koba.cart.box_added') }}</span>
        <span class="mesob-toast__title" data-mesob-toast-title></span>
    </span>
</div>

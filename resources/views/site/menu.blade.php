@php
    $sections = trans('koba.menu_page.sections');
    $categories = ['cakes' => $sections['cakes'], 'pastries' => $sections['pastries'],
        'drinks' => ['label' => 'Beverages', 'line' => __('koba.menu_page.drinks_line')],
        'chocolates' => ['label' => 'Chocolates', 'line' => '']];
    $categories += collect($sections)->except(['cakes', 'pastries'])->all();
    $selected = array_key_exists(request('category', 'cakes'), $categories) ? request('category', 'cakes') : 'cakes';
    $cakes = trans('koba.cakes');
    $dishImages = config('koba.dish_images');
@endphp

<x-site.layout page="menu" :title="__('koba.meta.pages.menu')" :description="__('koba.menu_page.lede')" image="almond-croissant">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.menu_page.eyebrow'),
        'title' => __('koba.menu_page.title'),
        'lede' => __('koba.menu_page.lede'),
        'slides' => [['almond-croissant', '50% 48%'], ['drink-flat-white', '50% 55%'], ['bomboloni', '50% 48%'], ['croissant', '50% 55%']],
    ])

    <div class="menu-bar" data-menu-bar>
        <div class="wrap menu-bar__inner">
            <nav class="menu-bar__nav" aria-label="{{ __('koba.menu_page.eyebrow') }}">
                <ul class="list-reset">
                    @foreach ($categories as $key => $category)
                        <li><a class="menu-bar__link {{ $selected === $key ? 'is-active' : '' }}"
                            href="{{ route('menu', ['category' => $key]) }}#{{ $key }}"
                            data-menu-category="{{ $key }}" @if ($selected === $key) aria-current="true" @endif>{{ $category['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <div class="menu-search" data-menu-search data-msg-results="{{ __('koba.menu_page.results') }}">
                <label class="sr-only" for="menu-search">{{ __('koba.menu_page.search') }}</label>
                <input id="menu-search" type="search" placeholder="{{ __('koba.menu_page.search') }}" autocomplete="off" data-menu-input>
            </div>
        </div>
    </div>

    <p class="sr-only" role="status" aria-live="polite" data-menu-status></p>
    <div class="menu-catalogue wrap">
        @foreach ($categories as $key => $category)
            @php
                $products = match ($key) {
                    'cakes' => collect($sections['cakes']['items'])->map(function ($name) use ($cakes, $dishImages) {
                        $slug = \Illuminate\Support\Str::slug($name);
                        return array_merge($cakes[$slug] ?? ['name' => $name], ['slug' => $slug, 'href' => route('cakes').'#cake-'.$slug]);
                    }),
                    'drinks' => collect(trans('koba.menu_page.drinks'))->map(fn ($drink) => ['name' => $drink['label'], 'image' => $drink['image'], 'slug' => \Illuminate\Support\Str::slug($drink['label'])]),
                    'chocolates' => collect(),
                    default => collect($category['items'])->map(function ($name) use ($dishImages) {
                        $slug = \Illuminate\Support\Str::slug($name);
                        return ['name' => $name, 'slug' => $slug, 'image' => $dishImages[$slug] ?? null];
                    }),
                };
            @endphp
            <section class="menu-category" id="{{ $key }}" aria-labelledby="menu-{{ $key }}" data-menu-section @if ($selected !== $key) hidden @endif>
                <header class="menu-category__head">
                    <h2 id="menu-{{ $key }}" class="display display--m">{{ $category['label'] }}</h2>
                    @if ($category['line']) <p class="body-copy">{{ $category['line'] }}</p> @endif
                </header>
                @if ($key === 'chocolates')
                    <p class="body-copy">No chocolate products are listed in this menu.</p>
                @else
                    <x-site.menu-products :products="$products" />
                @endif
            </section>
        @endforeach
        <p class="menu-empty" hidden data-menu-empty>{{ __('koba.menu_page.empty') }}</p>
    </div>

    <section class="band is-sand" aria-label="{{ __('koba.menu_page.cake_cta_title') }}">
        <div class="wrap band__inner">
            <p class="display display--m" data-reveal>{{ __('koba.menu_page.cake_cta_title') }}</p>
            <a class="btn" href="{{ route('order') }}" data-reveal>{{ __('koba.menu_page.cake_cta') }} <x-site.arrow /></a>
        </div>
    </section>
</x-site.layout>

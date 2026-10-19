@php
    $menuSections = trans('koba.menu_page.sections');
    $dishImages = config('koba.dish_images');
    $featuredCakes = collect(trans('koba.cakes'))->take(3)
        ->map(fn ($cake, $slug) => array_merge($cake, ['slug' => $slug, 'href' => route('cakes').'#cake-'.$slug]));
    $featuredPastries = collect($menuSections['pastries']['items'])->map(function ($name) use ($dishImages) {
        $slug = \Illuminate\Support\Str::slug($name);
        return ['name' => $name, 'slug' => $slug, 'image' => $dishImages[$slug] ?? null, 'href' => route('menu').'#dish-'.$slug];
    })->whereNotNull('image')->take(2);
    $featuredBeverages = collect(trans('koba.menu_page.drinks'))->take(1)
        ->map(fn ($drink) => array_merge($drink, ['name' => $drink['label'], 'slug' => \Illuminate\Support\Str::slug($drink['label']), 'href' => route('menu').'#drinks']));
    $featuredProducts = $featuredCakes->values()->concat($featuredPastries)->concat($featuredBeverages);
@endphp

<section class="menu-preview section is-cream-2" aria-labelledby="menu-preview-title">
    <div class="wrap">
        <header class="menu-preview__head">
            <div>
                <p class="eyebrow">{{ __('koba.menu_preview.eyebrow') }}</p>
                <h2 id="menu-preview-title" class="display display--l">A taste of KOBA</h2>
                <p class="body-copy">Explore a selection of our cakes, pastries, and beverages.</p>
            </div>
        </header>

        <x-site.menu-products :products="$featuredProducts" id-prefix="preview-dish-" :reveal="true" />
        <div class="menu-preview__action">
            <a class="btn" href="{{ route('menu') }}">Explore the Full Menu <x-site.arrow /></a>
        </div>
    </div>
</section>

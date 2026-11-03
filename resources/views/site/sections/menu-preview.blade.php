@php
    $featuredCakes = collect(trans('koba.cakes'))
        ->map(fn ($cake, $slug) => array_merge($cake, ['slug' => $slug, 'href' => route('cakes').'#cake-'.$slug]));
@endphp

<section class="menu-preview section is-cream-2" aria-labelledby="menu-preview-title" data-chapter="{{ __('koba.chapters.menu') }}">
    <x-site.leaf class="menu-preview__leaf" data-parallax="0.08" />
    <div class="wrap">
        <header class="menu-preview__head">
            <div>
                <p class="eyebrow">{{ __('koba.menu_preview.eyebrow') }}</p>
                <h2 id="menu-preview-title" class="display display--l">A taste of KOBA</h2>
                <p class="body-copy">Explore a selection of our signature cakes.</p>
            </div>
        </header>

        {{-- The way to the full menu, on its own row under the cakes. --}}
        <x-site.menu-products :products="$featuredCakes->values()" id-prefix="preview-dish-" :reveal="true">
            <a class="btn" href="{{ route('menu') }}">Explore the Full Menu <x-site.arrow /></a>
        </x-site.menu-products>
    </div>
</section>

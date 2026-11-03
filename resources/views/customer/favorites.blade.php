@extends('layouts.customer')

@section('title', 'Favorites')

@section('content')
<div x-data="{
        fav: @js($favorites),
        async toggle(type, value) {
            const data = await this.$action(@js(route('customer.favorites.toggle')), { type, value: String(value) }, 'POST');
            if (data) this.fav = data.favorites;
        },
     }">
    <x-dashboard.page-header eyebrow="Your KOBA" title="Favorites" description="Your cafés and the things you order again and again. Tap the heart to add or remove." />

    <section class="mb-8">
        <h2 class="mb-3 font-display text-[16px] font-semibold">Cafés</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="b in fav.branches" :key="b.key">
                <article class="card overflow-hidden">
                    <div class="relative h-40 bg-forest-900">
                        <img :src="b.image" alt="" loading="lazy" class="size-full object-cover opacity-90">
                        <button type="button" class="absolute top-3 right-3 grid size-9 place-items-center rounded-full bg-panel/95 shadow-[var(--shadow-card)] transition-transform active:scale-90"
                                @click="toggle('branch', b.key)" :aria-pressed="b.favorite" :aria-label="(b.favorite ? 'Remove ' : 'Add ') + b.name + (b.favorite ? ' from' : ' to') + ' favourites'">
                            <span x-html="$icon('heart', 'size-4 ' + (b.favorite ? 'fill-current text-danger' : 'text-ink-soft'))"></span>
                        </button>
                    </div>
                    <div class="p-4">
                        <p class="flex items-center justify-between gap-2 font-medium"><span x-text="b.name"></span><span x-html="$badge(b.status)"></span></p>
                        <p class="mt-1 text-xs text-ink-faint" x-text="b.area + ' · ' + b.hours"></p>
                        <div class="mt-3 flex gap-2">
                            <a :href="b.maps" target="_blank" rel="noopener" class="btn-secondary btn-sm"><span x-html="$icon('map-pin', 'size-3.5')"></span> Map</a>
                            <a :href="'tel:' + b.phone.replaceAll(' ', '')" class="btn-ghost btn-sm"><span x-html="$icon('phone', 'size-3.5')"></span> <span x-text="b.phone"></span></a>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <section>
        <div class="mb-3 flex items-end justify-between">
            <h2 class="font-display text-[16px] font-semibold">Items <span class="font-sans text-xs font-normal text-ink-faint" x-text="'· ' + fav.products.filter(p => p.favorite).length + ' saved'"></span></h2>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <template x-for="p in [...fav.products].sort((a, b) => b.favorite - a.favorite)" :key="p.id">
                <article class="card overflow-hidden">
                    <div class="relative">
                        <template x-if="p.image"><img :src="p.image" alt="" loading="lazy" class="aspect-square w-full object-cover"></template>
                        <template x-if="!p.image"><div class="grid aspect-square w-full place-items-center bg-forest-900 text-sand-300/80" x-html="$icon(p.icon, 'size-9')"></div></template>
                        <button type="button" class="absolute top-2 right-2 grid size-8 place-items-center rounded-full bg-panel/95 transition-transform active:scale-90"
                                @click="toggle('product', p.id)" :aria-pressed="p.favorite" :aria-label="(p.favorite ? 'Remove ' : 'Add ') + p.name">
                            <span x-html="$icon('heart', 'size-3.5 ' + (p.favorite ? 'fill-current text-danger' : 'text-ink-soft'))"></span>
                        </button>
                    </div>
                    <div class="p-3">
                        <p class="text-[11px] text-ink-faint" x-text="p.category"></p>
                        <p class="truncate text-[13.5px] font-medium" x-text="p.name"></p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-[13px] tabular-nums text-ink-soft" x-text="$fmt(p.price, 'etb')"></span>
                            <button type="button" class="btn-icon size-8 bg-accent-soft text-accent" @click="$reorder([p.cart])" :aria-label="'Add ' + p.name + ' to your mesob'"><span x-html="$icon('plus', 'size-4')"></span></button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>
</div>
@endsection

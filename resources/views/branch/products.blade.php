@extends('layouts.branch')

@section('title', 'Products')

@section('content')
<div x-data="{
        view: (() => { try { return localStorage.getItem('koba:products-view') || 'grid' } catch (e) { return 'grid' } })(),
        setView(v) { this.view = v; try { localStorage.setItem('koba:products-view', v) } catch (e) {} },
        async toggle(row) {
            row.available = !row.available;
            const ok = await this.$action(@js(route('branch.products.availability', '__ID__')).replace('__ID__', row.id), { available: row.available });
            if (!ok) row.available = !row.available;
        },
     }">
    <x-dashboard.page-header eyebrow="Menu" title="Products" description="What this café can serve today. Switching an item off hides it from pre-orders right away; prices are demo ETB.">
        <x-slot:actions>
            <div class="segmented" role="group" aria-label="View">
                <button type="button" :aria-pressed="view === 'grid'" @click="setView('grid')"><span class="inline-flex items-center gap-1"><x-dashboard.icon name="layout-grid" class="size-3.5" /> Grid</span></button>
                <button type="button" :aria-pressed="view === 'table'" @click="setView('table')"><span class="inline-flex items-center gap-1"><x-dashboard.icon name="list" class="size-3.5" /> Table</span></button>
            </div>
        </x-slot:actions>
    </x-dashboard.page-header>

    <x-dashboard.data-table
        :rows="$rows" :searchable="['name', 'title', 'sku']" search="Search the menu…" sort="units" :per-page="16" export-name="products"
        :filters="[
            'category' => ['label' => 'Category', 'options' => array_map(fn ($c) => $c['name'], $categories)],
            'stock' => ['label' => 'Stock', 'options' => ['ok' => 'In stock', 'low' => 'Low', 'out' => 'Sold out']],
        ]"
        :columns="[
            ['key' => 'name', 'label' => 'Product', 'type' => 'product', 'sortable' => true],
            ['key' => 'price', 'label' => 'Price', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
            ['key' => 'units', 'label' => 'Sold (7d)', 'type' => 'number', 'sortable' => true, 'align' => 'right'],
            ['key' => 'stock', 'label' => 'Stock', 'type' => 'badge'],
            ['key' => 'available', 'label' => 'Available'],
        ]">
        <x-slot:grid>
            {{-- Grid view lives inside the table component so search, filters and paging apply to it too --}}
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
                    <template x-for="row in rows" :key="row.id">
                        <article class="overflow-hidden rounded-xl border border-line bg-panel" :class="!row.available && 'opacity-60'">
                            <div class="relative aspect-[4/3] bg-forest-900">
                                <template x-if="row.image"><img :src="row.image" alt="" loading="lazy" class="size-full object-cover"></template>
                                <template x-if="!row.image"><div class="grid size-full place-items-center text-sand-300/80" x-html="$icon('croissant', 'size-10')"></div></template>
                                <span class="absolute top-2 left-2" x-show="row.stock !== 'ok'" x-html="$badge(row.stock, row.stock === 'low' ? row.left + ' left' : null)"></span>
                            </div>
                            <div class="p-3.5">
                                <p class="text-[11px] text-ink-faint" x-text="row.category_name"></p>
                                <p class="truncate font-medium" x-text="row.name"></p>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="font-display font-semibold tabular-nums" x-text="$fmt(row.price, 'etb')"></span>
                                    <button type="button" role="switch" :aria-checked="row.available" :aria-label="'Available: ' + row.name" @click="toggle(row)"
                                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors" :class="row.available ? 'bg-accent' : 'bg-ink/15'">
                                        <span class="inline-block size-4 rounded-full bg-white shadow transition-transform dark:bg-panel" :class="row.available ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-ink-faint"><span x-text="row.units"></span> sold this week</p>
                            </div>
                        </article>
                    </template>
                </div>
        </x-slot:grid>
        <x-slot:row>
            <tr :class="!row.available && 'opacity-60'">
                <td x-html="render(columns[0], row)"></td>
                <td class="text-right tabular-nums" x-text="$fmt(row.price, 'etb')"></td>
                <td class="text-right tabular-nums" x-text="$fmt(row.units)"></td>
                <td x-html="$badge(row.stock, row.stock === 'low' ? row.left + ' left' : null)"></td>
                <td>
                    <button type="button" role="switch" :aria-checked="row.available" :aria-label="'Available: ' + row.name" @click="toggle(row)"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors" :class="row.available ? 'bg-accent' : 'bg-ink/15'">
                        <span class="inline-block size-4 rounded-full bg-white shadow transition-transform dark:bg-panel" :class="row.available ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
                    </button>
                </td>
            </tr>
        </x-slot:row>
    </x-dashboard.data-table>
</div>
@endsection

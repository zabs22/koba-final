@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<x-dashboard.page-header eyebrow="Menu" title="Products"
    description="The KOBA menu shared by every café, with demo prices in ETB and the last 30 days of sales. Hiding a product removes it from every branch." />

<x-dashboard.data-table
    :rows="$rows" :searchable="['name', 'title', 'sku']" search="Search the menu…" sort="units" :per-page="15" export-name="products"
    :filters="['category' => ['label' => 'Category', 'options' => array_map(fn ($c) => $c['name'], $categories)]]"
    :columns="[
        ['key' => 'name', 'label' => 'Product', 'type' => 'product', 'sortable' => true],
        ['key' => 'sku', 'label' => 'SKU', 'type' => 'mono'],
        ['key' => 'price', 'label' => 'Price', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'units', 'label' => 'Sold (30d)', 'type' => 'number', 'sortable' => true, 'align' => 'right'],
        ['key' => 'revenue', 'label' => 'Revenue (30d)', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'active', 'label' => 'On the menu'],
    ]">
    <x-slot:row>
        <tr x-data="{ active: row.active }">
            <td x-html="render(columns[0], row)"></td>
            <td x-html="render(columns[1], row)"></td>
            <td class="text-right tabular-nums" x-text="$fmt(row.price, 'etb')"></td>
            <td class="text-right tabular-nums" x-text="$fmt(row.units)"></td>
            <td class="text-right font-medium tabular-nums" x-text="$fmt(row.revenue, 'etb')"></td>
            <td><x-dashboard.toggle model="active" label="On the menu" @click="active = !active; row.active = active; $action(@js(route('admin.products.update', '__ID__')).replace('__ID__', row.id), { active })" /></td>
        </tr>
    </x-slot:row>
    <x-slot:card>
        <li class="flex items-center gap-3 px-4 py-3" x-data="{ active: row.active }">
            <div class="min-w-0 flex-1" x-html="render(columns[0], row)"></div>
            <div class="text-right text-xs"><p class="font-medium tabular-nums" x-text="$fmt(row.price, 'etb')"></p><p class="text-ink-faint" x-text="row.units + ' sold'"></p></div>
            <x-dashboard.toggle model="active" label="On the menu" @click="active = !active; row.active = active; $action(@js(route('admin.products.update', '__ID__')).replace('__ID__', row.id), { active })" />
        </li>
    </x-slot:card>
</x-dashboard.data-table>
@endsection

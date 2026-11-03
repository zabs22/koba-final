@extends('layouts.customer')

@section('title', 'My orders')

@section('content')
<x-dashboard.page-header eyebrow="Your KOBA" title="My orders" description="Everything you've pre-ordered. Open an order for the receipt, or reorder it in one tap." />

<x-dashboard.data-table
    :rows="$rows" :searchable="['id', 'items', 'branch_name']" search="Search by item, café or order number…" sort="placed_at" row-event="open-order"
    :filters="[
        'status' => ['label' => 'Status', 'options' => \App\Services\Demo\DemoCatalog::STATUS_LABELS],
        'branch' => ['label' => 'Café', 'options' => array_column($branches, 'short', 'key')],
    ]"
    :columns="[
        ['key' => 'pickup_date', 'label' => 'Date'],
        ['key' => 'items', 'label' => 'Items', 'type' => 'truncate'],
        ['key' => 'branch_name', 'label' => 'Café', 'type' => 'muted'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ]"
    empty-title="No orders match" empty-body="Try another filter, or order something new from the menu.">
    <x-slot:card>
        <li class="flex items-center gap-3 px-4 py-3.5" @click="open(row)">
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13.5px] font-medium" x-text="row.items"></p>
                <p class="text-xs text-ink-faint" x-text="row.pickup_date + ' · ' + row.branch_name"></p>
            </div>
            <div class="text-right">
                <p class="text-[13px] font-medium tabular-nums" x-text="$fmt(row.total, 'etb')"></p>
                <span x-html="$badge(row.status)"></span>
            </div>
        </li>
    </x-slot:card>
</x-dashboard.data-table>
@endsection

@extends('layouts.branch')

@section('title', 'Orders')

@section('content')
<x-dashboard.page-header eyebrow="Service" title="Orders" description="Every order for this branch. Open one to move it along, cancel it or print the receipt.">
    <x-slot:actions><a href="{{ route('branch.live-orders') }}" class="btn-secondary"><x-dashboard.icon name="flame" /> Live board</a></x-slot:actions>
</x-dashboard.page-header>

<x-dashboard.data-table
    :endpoint="route('branch.orders.data')" :per-page="15" sort="placed_at" export-name="orders" selectable sum row-event="open-order" :poll="20"
    search="Search order number, customer or item…"
    :filters="[
        'range' => ['label' => 'Period', 'value' => 'today', 'options' => ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month']],
        'status' => ['label' => 'Status', 'options' => $statuses],
        'payment' => ['label' => 'Payment', 'options' => array_map(fn ($p) => $p['label'], $payments)],
    ]"
    :columns="[
        ['key' => 'id', 'label' => 'Order', 'type' => 'mono', 'sortable' => true],
        ['key' => 'customer', 'label' => 'Customer', 'type' => 'person', 'sub' => 'placed', 'sortable' => true],
        ['key' => 'items', 'label' => 'Items', 'type' => 'truncate', 'mobile' => false],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'payment_label', 'label' => 'Payment', 'type' => 'muted'],
        ['key' => 'pickup_time', 'label' => 'Pickup', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ]" />
@endsection

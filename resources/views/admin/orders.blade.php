@extends('layouts.admin')

@section('title', 'All orders')

@section('content')
<x-dashboard.page-header eyebrow="Operations" title="All orders" description="Every pre-order across the platform. Click an order for its full detail; new orders appear automatically." />

<x-dashboard.data-table
    :endpoint="route('admin.orders.data')" :per-page="15" sort="placed_at" export-name="orders" selectable sum row-event="open-order" :poll="30"
    search="Search order number, customer or item…"
    :filters="[
        'range' => ['label' => 'Period', 'value' => '7d', 'options' => ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month']],
        'branch' => ['label' => 'Branch', 'options' => array_column($branches, 'short', 'key')],
        'status' => ['label' => 'Status', 'options' => \App\Services\Demo\DemoCatalog::STATUS_LABELS],
        'payment' => ['label' => 'Payment', 'options' => array_map(fn ($p) => $p['label'], \App\Services\Demo\DemoCatalog::PAYMENTS)],
    ]"
    :columns="[
        ['key' => 'id', 'label' => 'Order', 'type' => 'mono', 'sortable' => true],
        ['key' => 'customer', 'label' => 'Customer', 'type' => 'person', 'sub' => 'branch_name', 'sortable' => true],
        ['key' => 'items', 'label' => 'Items', 'type' => 'truncate', 'mobile' => false],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'payment_label', 'label' => 'Payment', 'type' => 'muted'],
        ['key' => 'placed', 'label' => 'Placed', 'type' => 'muted', 'mobile' => false],
        ['key' => 'pickup_time', 'label' => 'Pickup', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ]" />
@endsection

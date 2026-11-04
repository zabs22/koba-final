@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
<x-dashboard.page-header eyebrow="Operations" title="Customers" description="Everyone who pre-orders from KOBA, with lifetime orders and spend. Segments update as people order." />

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach (['VIP' => 'award', 'Regular' => 'repeat', 'New' => 'user-plus', 'Occasional' => 'clock', 'Inactive' => 'circle-dot'] as $segment => $icon)
        <a href="?segment={{ $segment }}" class="card flex items-center gap-3 p-4 transition-colors hover:border-line-strong">
            <span class="grid size-9 place-items-center rounded-lg bg-accent-soft text-accent"><x-dashboard.icon :name="$icon" /></span>
            <div><p class="font-display text-xl font-semibold tabular-nums">{{ $segments[$segment] ?? 0 }}</p><p class="text-xs text-ink-faint">{{ $segment }}</p></div>
        </a>
    @endforeach
</div>

<x-dashboard.data-table
    :rows="$rows" :searchable="['name', 'email', 'phone']" search="Search name, email or phone…" sort="spent" export-name="customers" selectable
    :filters="[
        'segment' => ['label' => 'Segment', 'options' => array_combine(array_keys($segments), array_keys($segments))],
        'branch' => ['label' => 'Favourite café', 'options' => array_column($branches, 'short', 'key')],
    ]"
    :columns="[
        ['key' => 'name', 'label' => 'Customer', 'type' => 'person', 'sub' => 'email', 'sortable' => true],
        ['key' => 'phone', 'label' => 'Phone', 'type' => 'muted'],
        ['key' => 'branch_name', 'label' => 'Favourite café'],
        ['key' => 'orders', 'label' => 'Orders', 'type' => 'number', 'sortable' => true, 'align' => 'right'],
        ['key' => 'spent', 'label' => 'Total spent', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'last', 'label' => 'Last order', 'type' => 'muted'],
        ['key' => 'segment', 'label' => 'Segment', 'type' => 'badge'],
    ]" />
@endsection

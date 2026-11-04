@extends('layouts.branch')

@section('title', 'Customers')

@section('content')
@php
    $segments = array_count_values(array_column($rows, 'segment'));
@endphp

<x-dashboard.page-header eyebrow="Service" title="Customers" description="Everyone who has pre-ordered from this café, with their orders and spend here." />

<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach (['VIP' => ['award', '12+ orders here'], 'Regular' => ['repeat', '4–11 orders'], 'New' => ['user-plus', '1–3 orders']] as $segment => [$icon, $hint])
        <a href="?segment={{ $segment }}" class="card flex items-center gap-3 p-4 transition-colors hover:border-line-strong">
            <span class="grid size-10 place-items-center rounded-xl bg-accent-soft text-accent"><x-dashboard.icon :name="$icon" /></span>
            <div><p class="font-display text-xl font-semibold tabular-nums">{{ $segments[$segment] ?? 0 }}</p><p class="text-xs text-ink-faint">{{ $segment }} · {{ $hint }}</p></div>
        </a>
    @endforeach
</div>

<x-dashboard.data-table
    :rows="$rows" :searchable="['name', 'phone', 'email']" search="Search name or phone…" sort="spent" export-name="customers"
    :filters="['segment' => ['label' => 'Segment', 'options' => ['VIP' => 'VIP', 'Regular' => 'Regular', 'New' => 'New']]]"
    :columns="[
        ['key' => 'name', 'label' => 'Customer', 'type' => 'person', 'sub' => 'phone', 'sortable' => true],
        ['key' => 'orders', 'label' => 'Orders', 'type' => 'number', 'sortable' => true, 'align' => 'right'],
        ['key' => 'spent', 'label' => 'Spent here', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
        ['key' => 'last_at', 'label' => 'Last order', 'sortable' => true],
        ['key' => 'segment', 'label' => 'Segment', 'type' => 'badge'],
    ]">
    <x-slot:row>
        <tr>
            <td x-html="render(columns[0], row)"></td>
            <td class="text-right tabular-nums" x-text="$fmt(row.orders)"></td>
            <td class="text-right font-medium tabular-nums" x-text="$fmt(row.spent, 'etb')"></td>
            <td class="text-ink-soft" x-text="row.last"></td>
            <td x-html="$badge(row.segment)"></td>
        </tr>
    </x-slot:row>
</x-dashboard.data-table>
@endsection

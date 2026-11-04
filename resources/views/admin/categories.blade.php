@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<x-dashboard.page-header eyebrow="Menu" title="Categories" :description="'How the menu is grouped, and what each group earns · '.$range->label()" />

<div class="grid gap-5 xl:grid-cols-3">
    <x-dashboard.card class="xl:col-span-2" title="Revenue by category" :padded="false">
        <ul class="divide-y divide-line">
            @foreach (collect($rows)->sortByDesc('revenue') as $c)
                <li>
                    <a href="{{ route('admin.products', ['category' => $c['key']]) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-subtle">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-forest-900 text-sand-300"><x-dashboard.icon :name="$c['icon']" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $c['name'] }}</p>
                            <p class="text-xs text-ink-faint">{{ $c['products'] }} products</p>
                        </div>
                        <div class="hidden w-48 sm:block">
                            <div class="h-1.5 overflow-hidden rounded-full bg-subtle ring-1 ring-line ring-inset"><div class="h-full rounded-full bg-forest-500" style="width: {{ $c['share'] }}%"></div></div>
                            <p class="mt-1 text-right text-xs tabular-nums text-ink-faint">{{ $c['share'] }}% of revenue</p>
                        </div>
                        <p class="w-32 text-right font-medium tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($c['revenue']) }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-dashboard.card>

    <x-dashboard.card title="Share of revenue">
        <x-dashboard.chart type="donut" format="etb" :height="320" :href="route('admin.products').'?category={key}'"
            :data="['labels' => array_column($rows, 'name'), 'keys' => array_column($rows, 'key'), 'series' => array_column($rows, 'revenue')]" />
    </x-dashboard.card>
</div>
@endsection

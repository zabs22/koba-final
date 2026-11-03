@props(['item', 'badge' => null])

@php
    $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
    if ($item['route'] === 'branch.live-orders' && $badge === null) {
        $badge = count(array_filter(
            app(\App\Services\Demo\DemoDataService::class)->todayOrders(auth()->user()->branchKey()),
            fn ($o) => in_array($o['status'], ['pending', 'preparing', 'ready'], true),
        )) ?: null;
    }
@endphp

<a href="{{ route($item['route']) }}" class="nav-item" @if ($active) aria-current="page" @endif
   :class="collapsed && 'lg:justify-center lg:px-0'" :title="collapsed ? @js($item['label']) : null">
    <x-dashboard.icon :name="$item['icon']" class="size-[18px] {{ $active ? 'text-sand-400' : '' }}" />
    <span class="flex-1 truncate" :class="collapsed && 'lg:hidden'">{{ $item['label'] }}</span>
    @if ($badge)
        <span class="rounded-full bg-sand-400 px-1.5 text-[11px] font-semibold tabular-nums text-forest-900" :class="collapsed && 'lg:absolute lg:top-1 lg:right-2 lg:px-1'">{{ $badge }}</span>
    @endif
</a>

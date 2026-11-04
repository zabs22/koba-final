{{-- Customer: lighter top navigation (bottom tab bar on phones), same design system. --}}
@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $customerService = app(\App\Services\Dashboard\CustomerDashboardService::class);
    $navItems = \App\Support\Dashboard\Navigation::groups($user)[0]['items'];
    $notifications = $customerService->notifications($user);
    // Pickup pass for the sticky "My pickup QR" button (phones).
    $activeOrder = $customerService->active($user);
    $pickupPass = $activeOrder && in_array($activeOrder['status'], ['pending', 'preparing', 'ready'], true) ? $activeOrder : null;
@endphp

@section('body')
<div x-data class="min-h-full pb-20 md:pb-0">
    <header class="sticky top-0 z-30 border-b border-line bg-canvas/85 backdrop-blur supports-[backdrop-filter]:bg-canvas/75">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
            <a href="{{ route('customer.dashboard') }}" aria-label="KOBA — your dashboard" class="shrink-0"><x-site.logo class="h-[30px] w-auto" /></a>

            <nav class="hidden h-full items-stretch gap-1 md:flex" aria-label="Main">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       class="relative flex items-center px-3 text-[13.5px] font-medium transition-colors {{ $active ? 'text-ink after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-sand-500' : 'text-ink-soft hover:text-ink' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-1.5">
                <button type="button" class="btn-icon" @click="$dispatch('open-palette')" aria-label="Search (Ctrl K)"><x-dashboard.icon name="search" class="size-[18px]" /></button>
                <button type="button" class="btn-icon" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <x-dashboard.icon name="moon" class="size-[18px]" x-show="!$store.theme.dark" />
                    <x-dashboard.icon name="sun" class="size-[18px]" x-show="$store.theme.dark" x-cloak />
                </button>
                <x-dashboard.notifications :items="$notifications" :all="route('customer.notifications')" :read-url="route('customer.notifications.read')" />
                <a href="{{ route('menu') }}" class="btn-primary ml-1 hidden sm:inline-flex"><x-dashboard.icon name="shopping-bag" /> Order now</a>

                <div class="relative ml-1" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="Account"
                            class="grid size-9 place-items-center rounded-full bg-sand-400 font-display text-[13px] font-semibold text-forest-900 ring-2 ring-transparent transition hover:ring-sand-300">
                        {{ \App\Services\Demo\DemoCatalog::initials($user->name) }}
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak role="menu" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-panel py-1 shadow-[var(--shadow-pop)]">
                        <p class="border-b border-line px-3 py-2.5"><span class="block text-[13px] font-medium">{{ $user->name }}</span><span class="block truncate text-xs text-ink-faint">{{ $user->email }}</span></p>
                        <a role="menuitem" href="{{ route('customer.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="user-round" class="text-ink-faint" /> Profile</a>
                        <a role="menuitem" href="{{ route('menu') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="shopping-bag" class="text-ink-faint" /> Menu & cart</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                            @csrf
                            <button role="menuitem" class="flex w-full items-center gap-2.5 px-3 py-2 text-[13px] text-danger hover:bg-danger-soft"><x-dashboard.icon name="log-out" /> Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main id="main" tabindex="-1" class="mx-auto w-full max-w-6xl px-4 pt-6 pb-16 sm:px-6">
        @yield('content')
    </main>

    {{-- Phones: bottom tab bar --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-panel/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden" aria-label="Main">
        <ul class="grid grid-cols-5">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <li>
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif class="flex flex-col items-center gap-1 py-2.5 text-[10.5px] font-medium {{ $active ? 'text-accent' : 'text-ink-faint' }}">
                        <x-dashboard.icon :name="$item['icon']" class="size-5" />
                        {{ \Illuminate\Support\Str::of($item['label'])->replace('My ', '') }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @if ($pickupPass)
        <button type="button" x-data="{ pass: @js($pickupPass) }" @click="$dispatch('open-pickup-qr', pass)"
                class="fixed right-4 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-30 inline-flex h-12 items-center gap-2 rounded-full bg-forest-900 px-5 text-[14px] font-semibold text-cream shadow-[var(--shadow-pop)] ring-2 ring-sand-400/60 md:hidden">
            <x-dashboard.icon name="qr-code" class="size-5 text-sand-300" /> My pickup QR
        </button>
    @endif

    <x-pickup.qr-fullscreen />

    @include('dashboard.partials.order-drawer', ['area' => 'customer'])
</div>
@endsection

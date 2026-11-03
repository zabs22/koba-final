{{-- Admin / branch shell: dark-green sidebar (collapsible; a drawer on mobile) + top bar + content. --}}
@php
    $user = auth()->user();
    $area = $user->role()->area();
    $groups = \App\Support\Dashboard\Navigation::groups($user);
    $branch ??= null;
@endphp

<div x-data="shell" class="flex min-h-full">
    {{-- Mobile scrim --}}
    <div x-show="drawer" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-forest-950/50 lg:hidden" @click="drawer = false"></div>

    <aside data-shell-sidebar
        class="fixed inset-y-0 left-0 z-50 flex w-[264px] -translate-x-full flex-col bg-rail text-cream transition-[width,transform] duration-200 lg:translate-x-0"
        :class="{ 'translate-x-0': drawer, 'lg:w-[76px]': collapsed }"
        aria-label="Sidebar"
        @keydown.escape.window="drawer = false"
    >
        {{-- Brand --}}
        <div class="flex h-16 shrink-0 items-center gap-3 px-5" :class="collapsed && 'lg:justify-center lg:px-0'">
            <a href="{{ route($area.'.dashboard') }}" class="block overflow-hidden" :class="collapsed ? 'lg:w-[21px]' : 'w-[104px]'" aria-label="KOBA dashboard">
                <x-site.logo variant="sand" class="block h-[30px] w-auto max-w-none" />
            </a>
            <button type="button" class="ml-auto grid size-8 place-items-center rounded-lg text-cream/60 hover:bg-white/5 hover:text-cream lg:hidden" @click="drawer = false" aria-label="Close menu">
                <x-dashboard.icon name="x" />
            </button>
        </div>

        {{-- Context: platform or branch --}}
        <div class="mx-3 mb-3 rounded-lg border border-white/8 bg-white/[0.03] px-3 py-2.5" x-show="!collapsed" x-transition.opacity>
            <p class="text-[10.5px] font-semibold tracking-[0.16em] text-sand-400/80 uppercase">{{ $context['label'] }}</p>
            <p class="mt-0.5 truncate font-display text-[13.5px] font-semibold text-cream">{{ $context['title'] }}</p>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4" aria-label="Main">
            @foreach ($groups as $group)
                @if ($group['label'])
                    <p class="mt-5 mb-1.5 px-3 text-[10.5px] font-semibold tracking-[0.16em] text-cream/35 uppercase" x-show="!collapsed">{{ $group['label'] }}</p>
                    <div class="mx-3 my-3 h-px bg-white/8" x-show="collapsed" x-cloak></div>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        <li><x-dashboard.nav-item :item="$item" /></li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        {{-- Account --}}
        <div class="relative shrink-0 border-t border-white/8 p-3" x-data="{ menu: false }" @click.outside="menu = false">
            <div class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]" aria-hidden="true"></div>
            <button type="button" class="relative flex w-full items-center gap-3 rounded-lg p-1.5 text-left hover:bg-white/5" @click="menu = !menu" :aria-expanded="menu" aria-haspopup="menu">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-sand-400 font-display text-[13px] font-semibold text-forest-900">{{ \App\Services\Demo\DemoCatalog::initials($user->name) }}</span>
                <span class="min-w-0 flex-1" x-show="!collapsed">
                    <span class="block truncate text-[13px] font-medium text-cream">{{ $user->name }}</span>
                    <span class="block truncate text-[11.5px] text-cream/50">{{ $user->role()->label() }}</span>
                </span>
                <x-dashboard.icon name="chevrons-up-down" class="size-4 text-cream/40" x-show="!collapsed" />
            </button>
            <div x-show="menu" x-transition.origin.bottom.left x-cloak role="menu"
                 class="absolute bottom-full left-3 z-10 mb-2 w-56 overflow-hidden rounded-xl border border-line bg-panel py-1 text-ink shadow-[var(--shadow-pop)]">
                <a role="menuitem" href="{{ route($area.'.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="user-round" class="text-ink-faint" /> Profile</a>
                <a role="menuitem" href="{{ route('home') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="external-link" class="text-ink-faint" /> KOBA website</a>
                <div class="my-1 h-px bg-line"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button role="menuitem" type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-[13px] text-danger hover:bg-danger-soft"><x-dashboard.icon name="log-out" /> Sign out</button>
                </form>
            </div>
        </div>

        <button type="button" class="absolute top-[22px] -right-3 hidden size-6 place-items-center rounded-full border border-line bg-panel text-ink-soft shadow-[var(--shadow-card)] hover:text-ink lg:grid"
                @click="toggle()" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <x-dashboard.icon name="chevron-left" class="size-3.5 transition-transform" ::class="collapsed && 'rotate-180'" />
        </button>
    </aside>

    <div data-shell-main class="flex min-w-0 flex-1 flex-col transition-[padding] duration-200 lg:pl-[264px]" :class="collapsed && 'lg:pl-[76px]'">
        @include('dashboard.partials.topbar', ['area' => $area, 'branch' => $branch])

        <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1480px] flex-1 px-4 pt-6 pb-16 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @if (in_array($area, ['admin', 'branch'], true))
        @include('dashboard.partials.order-drawer', ['area' => $area])
    @endif
</div>

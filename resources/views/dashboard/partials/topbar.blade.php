{{-- Top bar: menu (mobile), search / command palette, demo badge, branch controls, theme, notifications. --}}
@php
    $notifications = $area === 'branch'
        ? app(\App\Services\Dashboard\BranchDashboardService::class)->notifications($branch['key'])
        : array_map(fn ($a) => [
            'icon' => $a['level'] === 'danger' ? 'alarm-clock' : ($a['level'] === 'warning' ? 'triangle-alert' : 'info'),
            'tone' => $a['level'], 'title' => $a['title'], 'body' => $a['body'], 'when' => 'Now',
            'href' => route('admin.branches.show', $a['branch']),
        ], app(\App\Services\Dashboard\AdminDashboardService::class)->alerts());
@endphp

<header class="sticky top-0 z-30 border-b border-line bg-canvas/85 backdrop-blur supports-[backdrop-filter]:bg-canvas/75 no-print">
    <div class="mx-auto flex h-16 max-w-[1480px] items-center gap-2 px-4 sm:px-6 lg:px-8">
        <button type="button" class="btn-icon -ml-2 lg:hidden" @click="drawer = true" aria-label="Open menu">
            <x-dashboard.icon name="menu" class="size-5" />
        </button>

        <button type="button" @click="$dispatch('open-palette')"
                class="flex h-9 w-full max-w-sm items-center gap-2 rounded-lg border border-line bg-panel px-3 text-left text-[13px] text-ink-faint transition-colors hover:border-line-strong">
            <x-dashboard.icon name="search" class="size-4" />
            <span class="flex-1 truncate">Search orders, pages…</span>
            <kbd class="hidden rounded border border-line bg-subtle px-1.5 font-sans text-[11px] text-ink-faint sm:inline">Ctrl K</kbd>
        </button>

        <div class="ml-auto flex items-center gap-1.5">
            <x-dashboard.demo-badge class="hidden md:inline-flex" />

            @if ($branch)
                {{-- Branch: current time and open / paused / closed for pre-orders --}}
                <span x-data="wallClock" class="hidden items-center gap-1.5 px-2 font-display text-[13px] font-semibold tabular-nums text-ink-soft xl:inline-flex">
                    <x-dashboard.icon name="clock" class="size-4 text-ink-faint" /><span x-text="now"></span>
                </span>
                <div x-data="{ status: @js($branch['status']), open: false }" class="relative" @click.outside="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-[13px] font-medium transition-colors"
                            :class="{ 'border-success/30 bg-success-soft text-success': status === 'open', 'border-warning/30 bg-warning-soft text-warning': status === 'paused', 'border-line bg-subtle text-ink-soft': status === 'closed' }">
                        <span class="size-2 rounded-full" :class="{ 'bg-success animate-pulse': status === 'open', 'bg-warning': status === 'paused', 'bg-ink-faint': status === 'closed' }"></span>
                        <span x-text="{ open: 'Open', paused: 'Paused', closed: 'Closed' }[status]"></span>
                        <x-dashboard.icon name="chevron-down" class="size-3.5 opacity-70" />
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak class="absolute right-0 z-20 mt-2 w-64 rounded-xl border border-line bg-panel p-1.5 shadow-[var(--shadow-pop)]">
                        @foreach (['open' => ['Open', 'Taking pre-orders now'], 'paused' => ['Pause new orders', 'Finish the queue, take no new orders'], 'closed' => ['Closed', 'Closed for the rest of the day']] as $value => [$label, $hint])
                            <button type="button" class="flex w-full items-start gap-2.5 rounded-lg px-2.5 py-2 text-left hover:bg-subtle"
                                    @click="$action('{{ route('branch.status') }}', { status: '{{ $value }}' }).then(r => { if (r) { status = r.status; open = false } })">
                                <span class="mt-1 size-2 rounded-full {{ ['open' => 'bg-success', 'paused' => 'bg-warning', 'closed' => 'bg-ink-faint'][$value] }}"></span>
                                <span><span class="block text-[13px] font-medium">{{ $label }}</span><span class="block text-xs text-ink-faint">{{ $hint }}</span></span>
                                <x-dashboard.icon name="check" class="ml-auto size-4 text-accent" x-show="status === '{{ $value }}'" />
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <button type="button" class="btn-icon" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">
                <x-dashboard.icon name="moon" class="size-[18px]" x-show="!$store.theme.dark" />
                <x-dashboard.icon name="sun" class="size-[18px]" x-show="$store.theme.dark" x-cloak />
            </button>

            <x-dashboard.notifications :items="$notifications" :all="$area === 'branch' ? route('branch.live-orders') : route('admin.dashboard')" />

            {{-- Account: profile and sign out --}}
            @php $user = auth()->user(); @endphp
            <div class="relative ml-1" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu"
                        class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition-colors hover:bg-accent-soft">
                    <span class="grid size-8 place-items-center rounded-full bg-sand-400 font-display text-[12.5px] font-semibold text-forest-900">{{ \App\Services\Demo\DemoCatalog::initials($user->name) }}</span>
                    <span class="hidden text-left leading-tight lg:block">
                        <span class="block text-[13px] font-medium text-ink">{{ $user->name }}</span>
                        <span class="block text-[11px] text-ink-faint">{{ $user->role()->label() }}</span>
                    </span>
                    <x-dashboard.icon name="chevron-down" class="size-3.5 text-ink-faint" />
                </button>
                <div x-show="open" x-transition.origin.top.right x-cloak role="menu"
                     class="absolute right-0 z-40 mt-2 w-60 overflow-hidden rounded-xl border border-line bg-panel py-1 shadow-[var(--shadow-pop)]">
                    <div class="border-b border-line px-3 py-2.5">
                        <p class="truncate text-[13px] font-medium">{{ $user->name }}</p>
                        <p class="truncate text-xs text-ink-faint">{{ $user->email }}</p>
                    </div>
                    <a role="menuitem" href="{{ route($area.'.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="user-round" class="text-ink-faint" /> Profile</a>
                    <a role="menuitem" href="{{ route('profile.show') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="shield-check" class="text-ink-faint" /> Security</a>
                    <a role="menuitem" href="{{ route('home') }}" class="flex items-center gap-2.5 px-3 py-2 text-[13px] hover:bg-subtle"><x-dashboard.icon name="external-link" class="text-ink-faint" /> KOBA website</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-line pt-1">
                        @csrf
                        <button role="menuitem" type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-[13px] font-medium text-danger hover:bg-danger-soft"><x-dashboard.icon name="log-out" /> Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

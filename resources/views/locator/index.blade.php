{{-- Find the nearest café: full-screen KOBA map + panel (bottom sheet on phones, side card on desktop).
     Locates the visitor, ranks the cafés by driving time (App\Services\Locator\BranchLocator) and hands
     the choice back to the form that asked. Map: resources/js/dashboard/locator-map.js --}}
@extends('layouts.dashboard')

@section('title', __('koba.locator.title'))

@section('body')
@php
    $t = trans('koba.locator');
    $config = [
        'urls' => ['nearest' => route('locator.nearest'), 'route' => route('locator.route'), 'style' => config('koba.locator.style_url')],
        'branches' => $branches,
        'purpose' => $for,
        'returnTo' => $returnTo,
        'demo' => $demoLocation,
        't' => $t,
    ];
@endphp
{{-- The public site is light; this page is always the KOBA night map with a cream panel. --}}
<script>document.documentElement.classList.remove('dark')</script>

<div x-data="locator(@js($config))" class="locator relative h-dvh w-full overflow-hidden bg-forest-950 text-cream">
    {{-- MapLibre makes its container position: relative, so the full-screen box is a wrapper. --}}
    <div class="absolute inset-0"><div x-ref="map" class="size-full" role="region" aria-label="{{ $t['title'] }}"></div></div>
    <div class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.07] transition-opacity duration-1000" :class="mapReady && '!opacity-0'" aria-hidden="true"></div>

    {{-- Radar until the first fix (centred on the part of the screen the panel leaves free) --}}
    <div x-show="state === 'locating' && !fix" x-transition.opacity.duration.500ms class="locator-radar top-[22%] left-1/2 lg:top-1/2 lg:left-[calc(50%+220px)]" aria-hidden="true"><span></span><span></span><span></span></div>

    {{-- Top bar --}}
    <header class="pointer-events-none absolute inset-x-0 top-0 z-20 flex items-start gap-2 p-3 sm:p-5">
        <a href="{{ $back }}" class="pointer-events-auto inline-flex h-11 items-center gap-2 rounded-full bg-forest-950/85 px-4 text-[14px] font-medium text-cream shadow-lg ring-1 ring-white/10 backdrop-blur transition hover:bg-forest-900">
            <x-dashboard.icon name="arrow-left" class="size-4" /> {{ $t['back'] }}
        </a>
        <a href="{{ route('home') }}" class="pointer-events-auto hidden h-11 items-center rounded-full bg-forest-950/85 px-4 shadow-lg ring-1 ring-white/10 backdrop-blur sm:inline-flex" aria-label="KOBA home">
            <x-site.logo variant="sand" class="h-[22px] w-auto" />
        </a>
        <div class="pointer-events-auto ml-auto flex flex-col items-end gap-2" x-show="mapReady && (state === 'results')" x-cloak>
            <div class="flex rounded-full bg-forest-950/85 p-1 shadow-lg ring-1 ring-white/10 backdrop-blur" role="group" aria-label="Map view">
                <button type="button" class="h-9 rounded-full px-3.5 text-[13px] font-semibold transition" :class="threeD ? 'bg-sand-400 text-forest-950' : 'text-cream/75 hover:text-cream'" :aria-pressed="threeD" @click="threeD || toggle3D()">{{ $t['view_3d'] }}</button>
                <button type="button" class="h-9 rounded-full px-3.5 text-[13px] font-semibold transition" :class="!threeD ? 'bg-sand-400 text-forest-950' : 'text-cream/75 hover:text-cream'" :aria-pressed="!threeD" @click="threeD && toggle3D()">{{ $t['view_2d'] }}</button>
            </div>
            <button type="button" class="grid size-11 place-items-center rounded-full bg-forest-950/85 text-cream shadow-lg ring-1 ring-white/10 backdrop-blur hover:bg-forest-900" @click="recenter()" aria-label="{{ $t['recenter'] }}" title="{{ $t['recenter'] }}">
                <x-dashboard.icon name="navigation" class="size-[18px]" />
            </button>
        </div>
    </header>

    {{-- Panel --}}
    <section x-ref="panel" aria-labelledby="locator-title"
             class="absolute inset-x-0 bottom-0 z-30 flex max-h-[58dvh] flex-col rounded-t-[28px] bg-cream text-forest-900 shadow-[0_-24px_60px_rgb(3_35_26/0.5)] lg:inset-y-5 lg:right-auto lg:left-5 lg:max-h-none lg:w-[420px] lg:rounded-[28px]">
        <div class="mx-auto mt-2.5 h-1.5 w-11 shrink-0 rounded-full bg-forest-900/15 lg:hidden" aria-hidden="true"></div>
        <h1 id="locator-title" class="sr-only">{{ $t['title'] }}</h1>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pt-3 pb-4 sm:px-6 lg:pt-6">

            {{-- Finding you / checking the roads --}}
            <div x-show="state === 'locating' || state === 'ranking'">
                <p class="text-[11px] font-semibold tracking-[0.16em] text-brown-600 uppercase">{{ $t['title'] }}</p>
                <p class="mt-1 font-display text-[24px] leading-tight font-semibold" x-text="state === 'ranking' ? t.ranking : t.locating"></p>
                <p class="mt-1.5 text-[13.5px] text-forest-900/65" x-show="state === 'locating'">{{ $t['locating_body'] }}</p>

                <div class="mt-4" x-show="fix" x-cloak>
                    <div class="h-1.5 overflow-hidden rounded-full bg-forest-900/10"><div class="h-full rounded-full bg-[#3d8bfd] transition-[width] duration-700" :style="`width: ${sharpness()}%`"></div></div>
                    <p class="mt-1.5 flex items-center gap-1.5 text-[12.5px] text-forest-900/60"><x-dashboard.icon name="circle-dot" class="size-3.5 text-[#3d8bfd]" /> <span x-text="accuracy()"></span></p>
                </div>

                <div class="mt-5 space-y-2.5" x-show="state === 'ranking'" x-cloak>
                    @for ($i = 0; $i < 3; $i++)<div class="h-14 animate-pulse rounded-2xl bg-forest-900/[0.07]"></div>@endfor
                </div>

                <div class="mt-5 flex flex-wrap gap-2" x-show="state === 'locating'">
                    <a href="{{ $back }}" class="inline-flex h-11 items-center gap-2 rounded-full border border-forest-900/20 px-4 text-[14px] font-medium hover:bg-forest-900/5"><x-dashboard.icon name="list" class="size-4" /> {{ $t['pick'] }}</a>
                    @if ($demoLocation)
                        <button type="button" class="inline-flex h-11 items-center gap-2 rounded-full px-3 text-[13px] font-medium text-brown-600 hover:bg-forest-900/5" @click="useDemo()"><x-dashboard.icon name="sparkles" class="size-4" /> {{ $t['demo'] }}</button>
                    @endif
                </div>
            </div>

            {{-- Something stopped us --}}
            <div x-show="state === 'error'" x-cloak>
                <span class="grid size-12 place-items-center rounded-2xl bg-forest-900 text-sand-300"><x-dashboard.icon name="map-pin" class="size-6" /></span>
                <p class="mt-3 font-display text-[21px] leading-tight font-semibold" x-text="t.errors[error]?.[0]"></p>
                <p class="mt-1.5 text-[13.5px] text-forest-900/65" x-text="t.errors[error]?.[1]"></p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" x-show="error !== 'insecure' && error !== 'unsupported'" class="inline-flex h-11 items-center gap-2 rounded-full bg-forest-900 px-5 text-[14px] font-semibold text-cream hover:bg-forest-800" @click="locate()"><x-dashboard.icon name="rotate-ccw" class="size-4" /> {{ $t['retry'] }}</button>
                    @if ($demoLocation)
                        <button type="button" class="inline-flex h-11 items-center gap-2 rounded-full px-3 text-[13px] font-medium text-brown-600 hover:bg-forest-900/5" @click="useDemo()"><x-dashboard.icon name="sparkles" class="size-4" /> {{ $t['demo'] }}</button>
                    @endif
                </div>
            </div>

            {{-- Not in Addis --}}
            <div x-show="state === 'far'" x-cloak>
                <span class="grid size-12 place-items-center rounded-2xl bg-forest-900 text-sand-300"><x-dashboard.icon name="navigation" class="size-6" /></span>
                <p class="mt-3 font-display text-[21px] leading-tight font-semibold" x-text="result && t.far.replace(':distance', road({ road_m: result.branches[0].straight_m }))"></p>
                <p class="mt-1.5 text-[13.5px] text-forest-900/65">{{ $t['far_body'] }}</p>
                @if ($demoLocation)
                    <button type="button" class="mt-3 inline-flex h-10 items-center gap-2 rounded-full px-1 text-[13px] font-medium text-brown-600 hover:underline" @click="useDemo()"><x-dashboard.icon name="sparkles" class="size-4" /> {{ $t['demo'] }}</button>
                @endif
            </div>

            {{-- The chosen café --}}
            <template x-if="picked && (state !== 'locating' && state !== 'ranking')">
                <article class="mt-4 first:mt-0" :class="state === 'results' ? '' : 'rounded-3xl bg-white/70 p-4 ring-1 ring-forest-900/10'">
                    <p class="text-[11px] font-semibold tracking-[0.16em] text-brown-600 uppercase" x-show="state === 'results'" x-text="selected === result?.recommended ? t.nearest : picked.short"></p>
                    <div class="mt-2 flex gap-4">
                        <img :src="picked.image" alt="" class="size-[72px] shrink-0 rounded-2xl object-cover ring-1 ring-forest-900/10" x-show="picked.image">
                        <div class="min-w-0">
                            <h2 class="font-display text-[22px] leading-tight font-semibold" x-text="picked.short"></h2>
                            <p class="text-[13px] text-forest-900/60" x-text="(picked.mood ? picked.mood + ' · ' : '') + picked.area"></p>
                            <p class="mt-1 inline-flex items-center gap-1.5 text-[12.5px] font-medium" :class="picked.open_now ? 'text-[#0f7a52]' : 'text-forest-900/55'">
                                <span class="size-1.5 rounded-full" :class="picked.open_now ? 'bg-[#0f7a52]' : 'bg-forest-900/40'"></span><span x-text="picked.hours"></span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 divide-x divide-white/10 rounded-2xl bg-forest-900 py-3 text-center text-cream" x-show="state === 'results'">
                        <div><p class="font-display text-[22px] leading-none font-semibold" x-text="eta(picked)"></p><p class="mt-1 text-[11.5px] text-cream/60">{{ $t['by_car'] }}</p></div>
                        <div><p class="font-display text-[22px] leading-none font-semibold" x-text="road(picked)"></p><p class="mt-1 text-[11.5px] text-cream/60" x-text="t.compass[picked.compass] ?? ''"></p></div>
                        <div><p class="font-display text-[17px] leading-[22px] font-semibold" x-text="walkTime(picked)"></p><p class="mt-1 text-[11.5px] text-cream/60">{{ $t['on_foot'] }}</p></div>
                    </div>
                    <p class="mt-2 text-[12px] text-forest-900/55" x-show="state === 'results' && picked.estimated">{{ $t['estimated'] }}</p>
                    <p class="mt-2 rounded-xl bg-sand-100 px-3 py-2 text-[12.5px] text-brown-600" x-show="skipped" x-text="skipped && t.skipped.replace(':branch', skipped.short)"></p>
                    <p class="mt-2 rounded-xl bg-sand-100 px-3 py-2 text-[12.5px] text-brown-600" x-show="purpose === 'order' && !picked.takes_orders">{{ $t['paused'] }}</p>

                    <div class="mt-4 flex gap-2">
                        <button type="button" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-full bg-forest-900 px-5 text-[15px] font-semibold text-cream shadow-[0_10px_24px_rgb(6_46_34/0.25)] transition hover:bg-forest-800 disabled:opacity-40" :disabled="!canUse" @click="use(selected)">
                            <x-dashboard.icon name="check" class="size-[18px]" /> <span>{{ $for ? $t['use'] : $t['order_here'] }}</span>
                        </button>
                        <a :href="picked.directions" target="_blank" rel="noopener" class="grid size-12 place-items-center rounded-full border border-forest-900/20 hover:bg-forest-900/5" aria-label="{{ $t['directions'] }}" title="{{ $t['directions'] }}"><x-dashboard.icon name="navigation" class="size-[18px]" /></a>
                        <a :href="'tel:' + picked.phone.replace(/\s/g, '')" class="grid size-12 place-items-center rounded-full border border-forest-900/20 hover:bg-forest-900/5" aria-label="{{ $t['call'] }}" title="{{ $t['call'] }}"><x-dashboard.icon name="phone" class="size-[18px]" /></a>
                    </div>
                </article>
            </template>

            {{-- Other cafés (or all of them, to choose by hand) --}}
            <section class="mt-6" x-show="state !== 'locating' && state !== 'ranking'" x-cloak>
                <h2 class="text-[11px] font-semibold tracking-[0.16em] text-forest-900/50 uppercase" x-text="state === 'results' ? t.others : t.pick"></h2>
                <ul class="mt-2 divide-y divide-forest-900/10">
                    <template x-for="b in rows" :key="b.key">
                        <li>
                            <button type="button" class="flex w-full items-center gap-3 py-3 text-left transition hover:bg-forest-900/[0.03]" :class="selected === b.key && state !== 'results' ? 'bg-forest-900/[0.04]' : ''" @click="select(b.key)" :aria-pressed="selected === b.key">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl font-display text-[15px] font-bold" :class="b.takes_orders ? 'bg-sand-400 text-forest-950' : 'bg-forest-900/10 text-forest-900/50'">K</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[14.5px] font-semibold" x-text="b.short"></span>
                                    <span class="block truncate text-[12.5px] text-forest-900/55" x-text="b.takes_orders ? (b.straight_m ? away(b) : b.area) : t.paused"></span>
                                </span>
                                <span class="text-right" x-show="state === 'results'">
                                    <span class="block font-display text-[15px] font-semibold" x-text="eta(b)"></span>
                                    <span class="block text-[12px] text-forest-900/55" x-text="road(b)"></span>
                                </span>
                                <x-dashboard.icon name="chevron-right" class="size-4 text-forest-900/35" x-show="state !== 'results'" />
                            </button>
                        </li>
                    </template>
                </ul>
            </section>

            <p class="mt-4 text-[12.5px] text-forest-900/60" x-show="mapFailed" x-cloak>{{ $t['map_unavailable'] }}</p>
        </div>

        <footer class="shrink-0 space-y-0.5 border-t border-forest-900/10 px-5 py-3 text-[11px] leading-snug text-forest-900/50 sm:px-6">
            <p class="flex items-start gap-1.5"><x-dashboard.icon name="lock" class="mt-px size-3 shrink-0" /> {{ $t['privacy'] }}</p>
            <p>{{ $t['credits'] }}</p>
        </footer>
    </section>

    <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
</div>
@endsection

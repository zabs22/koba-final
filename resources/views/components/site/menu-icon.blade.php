@props(['name' => null])

{{-- Line icons for the menu sections, drawn in currentColor. Unknown sections fall back to the ኮባ leaf glyph. --}}
@php
    $paths = [
        // A layered cake with a candle.
        'cakes' => '<path d="M12 52h40M14 52V38h36v14M18 38V28h28v10M14 45c4 3 8 3 12 0s8-3 12 0 8 3 12 0M32 28v-8"/><path d="M32 13c2 2.5 2 4.5 0 6-2-1.5-2-3.5 0-6z"/>',
        // A croissant.
        'pastries' => '<path d="M10 40c6-14 16-21 22-21s16 7 22 21c-4 3-8 3-11 1l-3-12M10 40c4 3 8 3 11 1l3-12M24 29c2-4 5-6 8-6s6 2 8 6M27 41l5-17 5 17"/>',
        // A coffee cup with steam.
        'beverages' => '<path d="M14 28h30v12a12 12 0 0 1-12 12h-6a12 12 0 0 1-12-12V28zM44 32h4a5 5 0 0 1 0 10h-5M10 56h38"/><path d="M23 22c-2-3 2-5 0-8M31 22c-2-3 2-5 0-8M39 22c-2-3 2-5 0-8"/>',
        // A bar of chocolate, one corner broken.
        'chocolates' => '<path d="M16 12h32v40H26l-10-10V12zM16 26h32M16 40h32M32 12v40"/>',
        // Morning sun over the horizon.
        'breakfast' => '<path d="M8 44h48M18 44a14 14 0 0 1 28 0M32 18v6M14 26l4 4M50 26l-4 4M8 52h48"/>',
        // A plate under a cloche.
        'dishes' => '<path d="M10 46h44M14 46a18 18 0 0 1 36 0M32 24v-4M28 20h8M8 52h48"/>',
    ];
    $paths['drinks'] = $paths['beverages'];
@endphp

@if (isset($paths[$name]))
    <svg {{ $attributes->class('menu-icon') }} viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] !!}</svg>
@else
    <x-site.mark {{ $attributes->class('menu-icon') }} />
@endif

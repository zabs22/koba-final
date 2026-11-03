@props(['name'])
{{-- Lucide icon placeholder, replaced by an inline SVG in resources/js/dashboard/icons.js. --}}
<i data-icon="{{ $name }}" {{ $attributes->merge(['class' => 'inline-block size-4 shrink-0']) }} aria-hidden="true"></i>

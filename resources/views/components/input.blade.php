@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'h-11 bg-white border-koba-900/20 text-koba-900 placeholder:text-koba-900/40 focus:border-koba-700 focus:ring-koba-700/20 focus:ring-2 rounded-lg shadow-xs']) !!}>

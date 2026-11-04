@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-[13px] text-koba-900/75']) }}>
    {{ $value ?? $slot }}
</label>

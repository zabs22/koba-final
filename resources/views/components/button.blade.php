<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 h-11 bg-koba-700 border border-transparent rounded-lg font-semibold text-sm text-koba-cream hover:bg-koba-900 focus:bg-koba-900 active:bg-koba-950 focus:outline-hidden focus:ring-2 focus:ring-koba-sand focus:ring-offset-2 focus:ring-offset-koba-cream disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

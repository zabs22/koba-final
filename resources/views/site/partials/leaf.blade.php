@php
    // The koba (ensete) leaf from the brand guideline: a long blade, a midrib,
    // and the soft tears along its edge. Fill comes from `color` in CSS.
    $uid = 'leaf-'.($id ?? uniqid());
@endphp
<svg class="{{ $class ?? '' }}" viewBox="0 0 240 640" aria-hidden="true" focusable="false">
    <defs>
        <mask id="{{ $uid }}">
            <path fill="#fff" d="M120 4c58 54 104 150 106 290 2 132-38 250-106 342C52 544 12 426 14 294 16 154 62 58 120 4z"/>
            <g fill="none" stroke="#000" stroke-width="7" stroke-linecap="round">
                <path d="M226 210 172 252"/><path d="M230 352 168 392"/><path d="M214 470 158 512"/>
                <path d="M14 168 70 214"/><path d="M12 300 74 344"/><path d="M28 440 84 486"/>
            </g>
            <path fill="none" stroke="#000" stroke-width="5" d="M120 30c-4 160-4 380 0 600"/>
        </mask>
    </defs>
    <rect width="240" height="640" fill="currentColor" mask="url(#{{ $uid }})"/>
</svg>

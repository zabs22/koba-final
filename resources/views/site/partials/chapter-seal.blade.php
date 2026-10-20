{{-- Our Story: circular café seal — text on a ring around the leaf mark. Decorative. --}}
<span class="chapter__seal" aria-hidden="true">
    <svg class="chapter__seal-ring" viewBox="0 0 200 200" focusable="false">
        <defs><path id="chapter-seal-{{ $i }}" d="M100,100 m-76,0 a76,76 0 1,1 152,0 a76,76 0 1,1 -152,0" /></defs>
        <text><textPath href="#chapter-seal-{{ $i }}" textLength="472" lengthAdjust="spacing">{{ mb_strtoupper($text) }}</textPath></text>
    </svg>
    <x-site.mark class="chapter__seal-mark" />
</span>

{{--
    A KOBA table seen from above, laid for each mood in $xp['feel'] order:
    friends (four chairs, cups, bomboloni), a quiet break (one chair, a cup, a book,
    a croissant) and a celebration (a candle-lit cake, six places). experience.js
    swaps the settings with the sentence; chairs slide in, things set down one by one.
--}}
@php
    // A point on the table: angle 0 points down, turning clockwise; radius from the centre.
    $pt = fn ($deg, $r) => [round(200 - $r * sin(deg2rad($deg)), 1), round(200 + $r * cos(deg2rad($deg)), 1)];
    $sets = [
        ['chairs' => [45, 135, 225, 315], 'cups' => [45, 135, 225, 315], 'cupR' => 100],
        ['chairs' => [20], 'cups' => [20], 'cupR' => 92],
        ['chairs' => [0, 60, 120, 180, 240, 300], 'cups' => [], 'cupR' => 0],
    ];
@endphp
<svg class="xp-table" viewBox="-20 -20 440 440" aria-hidden="true" focusable="false">
    <defs>
        <radialGradient id="xp-table-glow"><stop offset="0" stop-color="#ffd27a" stop-opacity=".8"/><stop offset="1" stop-color="#ffd27a" stop-opacity="0"/></radialGradient>
    </defs>
    <g class="xp-table__spin">
        @foreach ($sets as $s => $set)
            <g class="xp-table__chairs {{ $s === 0 ? 'is-on' : '' }}" data-xp-feel-scene-chairs>
                @foreach ($set['chairs'] as $deg)
                    <g transform="rotate({{ $deg }} 200 200)"><rect class="xp-table__chair" x="170" y="356" width="60" height="26" rx="12" style="--i: {{ $loop->index }}"/></g>
                @endforeach
            </g>
        @endforeach

        {{-- The tabletop: warm wood with faint rings --}}
        <circle class="xp-table__top" cx="200" cy="200" r="152"/>
        <circle class="xp-table__ring" cx="200" cy="200" r="122"/>
        <circle class="xp-table__ring" cx="200" cy="200" r="84"/>

        @foreach ($sets as $s => $set)
            <g class="xp-table__set {{ $s === 0 ? 'is-on' : '' }}" data-xp-feel-scene>
                @foreach ($set['cups'] as $deg)
                    @php([$x, $y] = $pt($deg, $set['cupR']))
                    <g transform="translate({{ $x }} {{ $y }}) rotate({{ $deg + 90 }})">
                        <g class="xp-table__item" style="--i: {{ $loop->index }}">
                            <circle class="xp-table__aroma" r="17"/>
                            <circle class="xp-table__saucer" r="27"/>
                            <rect class="xp-table__china" x="13" y="-4.5" width="12" height="9" rx="4.5"/>
                            <circle class="xp-table__cup" r="18"/>
                            <circle class="xp-table__coffee" r="14"/>
                            <path class="xp-table__art" d="M0 6C-9-1-6-9 0-4 6-9 9-1 0 6Z"/>
                        </g>
                    </g>
                @endforeach

                @if ($s === 0)
                    {{-- Bomboloni to share --}}
                    <g transform="translate(200 200)">
                        <g class="xp-table__item" style="--i: 4">
                            <circle class="xp-table__plate" r="40"/>
                            @foreach ([[-13, -9], [13, -9], [0, 13]] as [$bx, $by])
                                <circle class="xp-table__bun" cx="{{ $bx }}" cy="{{ $by }}" r="12"/>
                                <circle class="xp-table__sugar" cx="{{ $bx - 3 }}" cy="{{ $by - 3 }}" r="4"/>
                            @endforeach
                        </g>
                    </g>
                @elseif ($s === 1)
                    {{-- A book, closed for now, and a croissant --}}
                    <g transform="translate(238 172) rotate(-14)">
                        <g class="xp-table__item" style="--i: 1">
                            <rect class="xp-table__book" x="-36" y="-26" width="72" height="52" rx="3"/>
                            <rect class="xp-table__spine" x="-36" y="-26" width="8" height="52" rx="2"/>
                            <rect class="xp-table__title" x="-18" y="-6" width="38" height="3" rx="1.5"/>
                            <rect class="xp-table__title" x="-18" y="2" width="24" height="3" rx="1.5"/>
                        </g>
                    </g>
                    <g transform="translate(222 262) rotate(18)">
                        <g class="xp-table__item" style="--i: 2">
                            <circle class="xp-table__plate" r="30"/>
                            <path class="xp-table__croissant" d="M-23 6C-15-15 15-15 23 6 15-1-15-1-23 6Z"/>
                            <path class="xp-table__fold" d="M-9-10-6 2M0-12V1M9-10 6 2"/>
                        </g>
                    </g>
                @else
                    {{-- A cake with candles, and a place for everyone --}}
                    @foreach ([30, 90, 150, 210, 270, 330] as $deg)
                        @php([$x, $y] = $pt($deg, 108))
                        <g transform="translate({{ $x }} {{ $y }}) rotate({{ $deg }})">
                            <g class="xp-table__item" style="--i: {{ $loop->index }}">
                                <circle class="xp-table__plate" r="17"/>
                                <rect class="xp-table__fork" x="20" y="-12" width="2.5" height="24" rx="1.2"/>
                            </g>
                        </g>
                    @endforeach
                    <g transform="translate(200 200)">
                        <g class="xp-table__item" style="--i: 6">
                            <circle class="xp-table__plate" r="62"/>
                            <circle class="xp-table__cake" r="47"/>
                            <circle class="xp-table__drip" r="40"/>
                            @foreach (range(0, 4) as $k)
                                @php([$cx, $cy] = [round(22 * sin(deg2rad($k * 72)), 1), round(-22 * cos(deg2rad($k * 72)), 1)])
                                <circle class="xp-table__glow" cx="{{ $cx }}" cy="{{ $cy }}" r="13" fill="url(#xp-table-glow)" style="--k: {{ $k }}"/>
                                <circle class="xp-table__candle" cx="{{ $cx }}" cy="{{ $cy }}" r="4.5"/>
                                <circle class="xp-table__flame" cx="{{ $cx }}" cy="{{ $cy }}" r="2.6" style="--k: {{ $k }}"/>
                            @endforeach
                        </g>
                    </g>
                @endif
            </g>
        @endforeach
    </g>
</svg>

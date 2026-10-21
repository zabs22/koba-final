@php
    $atlas = trans('koba.locations.atlas');
    $branches = trans('koba.locations.branches');
    $labels = array_intersect_key($atlas, array_flip(['open_now', 'until', 'closed', 'opens']));
    $pins = array_values(array_map(fn ($b) => $b['pin'], $branches));
    $coords = fn ($b) => sprintf('%.2f° %s · %.2f° %s', abs($b['coords'][0]), $b['coords'][0] >= 0 ? 'N' : 'S', abs($b['coords'][1]), $b['coords'][1] >= 0 ? 'E' : 'W');

    // A softly curving trail that links the cafés in order.
    $trail = 'M'.implode(' ', $pins[0]);
    for ($i = 1; $i < count($pins); $i++) {
        [$x0, $y0] = $pins[$i - 1];
        [$x1, $y1] = $pins[$i];
        $cx = ($x0 + $x1) / 2 + ($y1 - $y0) * 0.35;
        $cy = ($y0 + $y1) / 2 - ($x1 - $x0) * 0.35;
        $trail .= " Q{$cx} {$cy} {$x1} {$y1}";
    }
@endphp

<section class="atlas" data-atlas aria-labelledby="atlas-title" data-labels="{{ json_encode($labels) }}">
    <header class="atlas__head">
        <div class="atlas__heading">
            <p class="eyebrow" data-reveal>{{ $atlas['eyebrow'] }}</p>
            <x-site.heading id="atlas-title" tag="h3" :text="$atlas['title']" size="m" />
        </div>
        <p class="lede atlas__lede" data-reveal>{{ $atlas['lede'] }}</p>
    </header>

    <div class="atlas__shell" data-reveal="media">
        {{-- Branch index --}}
        <aside class="atlas__side">
            <div class="atlas__side-head">
                <span class="atlas__dot" aria-hidden="true"></span>
                <span>{{ $atlas['list_label'] }}</span>
                <span class="atlas__count">{{ str_pad(count($branches), 2, '0', STR_PAD_LEFT) }}</span>
            </div>

            <ol class="list-reset atlas__list">
                @foreach ($branches as $slug => $branch)
                    <li class="atlas__item {{ $loop->first ? 'is-active' : '' }}" data-atlas-item="{{ $loop->index }}" data-slug="{{ $slug }}" data-open="{{ json_encode($branch['open']) }}" data-coords="{{ $coords($branch) }}">
                        <button class="atlas__pick" type="button" data-atlas-pick="{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="atlas__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="atlas__pick-text">
                                <span class="atlas__name">{{ $branch['short'] }}</span>
                                <span class="atlas__mood">{{ $branch['mood'] }} · {{ $branch['area'] }}</span>
                                <span class="atlas__status" data-atlas-status hidden></span>
                            </span>
                        </button>
                        <a class="atlas__go" href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($branch['maps']) }}" target="_blank" rel="noopener" aria-label="{{ $atlas['directions'] }}: {{ $branch['name'] }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.6 21.4 12 12 21.4 2.6 12z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 14.2v-2.4c0-.9.7-1.6 1.6-1.6h4.2m-1.9-2 2 2-2 2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                @endforeach
            </ol>

            <p class="atlas__coords" aria-hidden="true" data-atlas-coords>{{ $coords(array_values($branches)[0]) }}</p>
        </aside>

        {{-- Map stage --}}
        <div class="atlas__stage" data-atlas-stage>
            <div class="atlas__canvas" data-atlas-canvas>
                <svg class="atlas__map" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid slice" role="img" aria-label="{{ $atlas['map_label'] }}">
                    <defs>
                        <radialGradient id="atlas-glow" cx="52%" cy="55%" r="65%">
                            <stop offset="0" stop-color="#0d4a36"/>
                            <stop offset="1" stop-color="#03231a"/>
                        </radialGradient>
                        <pattern id="atlas-blocks" width="22" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate(-18)">
                            <rect x="2" y="2" width="16" height="9" rx="1.5"/>
                            <rect x="2" y="13" width="7" height="7" rx="1.5"/>
                            <rect x="11" y="13" width="7" height="7" rx="1.5"/>
                        </pattern>
                        <radialGradient id="atlas-fade" cx="52%" cy="56%" r="48%">
                            <stop offset="0" stop-color="#fff"/>
                            <stop offset="0.75" stop-color="#fff" stop-opacity="0.35"/>
                            <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                        </radialGradient>
                        <mask id="atlas-city"><rect width="1000" height="700" fill="url(#atlas-fade)"/></mask>
                        <path id="atlas-contour" d="M-20 120C110 70 230 150 350 105S560 30 700 85 900 50 1020 95"/>
                    </defs>

                    <rect width="1000" height="700" fill="url(#atlas-glow)"/>

                    {{-- Entoto hills --}}
                    <g class="atlas__contours">
                        @foreach ([[-62, 1], [-34, .85], [-6, .7], [22, .55], [50, .4], [78, .25]] as [$dy, $o])
                            <use href="#atlas-contour" transform="translate(0 {{ $dy }})" opacity="{{ $o }}"/>
                        @endforeach
                    </g>

                    {{-- City fabric --}}
                    <rect class="atlas__blocks" width="1000" height="700" fill="url(#atlas-blocks)" mask="url(#atlas-city)"/>

                    {{-- Parks & rivers --}}
                    <g class="atlas__parks">
                        <path d="M470 228c28-14 66-8 80 14s-6 48-40 52-60-8-62-30 4-26 22-36z"/>
                        <path d="M500 420c22-10 58-6 66 12s-12 34-40 34-46-8-46-22 6-16 20-24z"/>
                        <path d="M160 150c30-18 80-10 90 16s-20 50-60 48-60-20-56-38 10-18 26-26z"/>
                    </g>
                    <g class="atlas__rivers">
                        <path d="M110 0C170 120 130 250 220 360S300 560 250 700"/>
                        <path d="M640 0C610 110 690 200 660 300S720 470 690 560 740 660 760 700"/>
                    </g>

                    {{-- Roads --}}
                    <g class="atlas__roads">
                        <path class="is-ring" d="M150 380C160 230 330 150 520 150S880 230 900 380 760 640 540 640 140 540 150 380Z"/>
                        <path d="M420 0C410 150 450 260 470 380S480 560 470 700"/>
                        <path d="M470 380C560 420 650 470 760 520S900 600 1000 640"/>
                        <path d="M470 300C600 290 720 260 830 250S940 240 1000 235"/>
                        <path d="M0 330C150 340 300 360 470 380"/>
                        <path d="M470 380C400 470 330 560 240 700"/>
                        <path d="M430 200C500 150 560 90 620 0"/>
                        <path class="is-minor" d="M250 160C320 240 380 300 470 300"/>
                        <path class="is-minor" d="M600 640C640 560 700 520 760 520"/>
                        <path class="is-minor" d="M680 160C690 260 650 330 600 420"/>
                    </g>

                    {{-- Districts --}}
                    <g class="atlas__labels">
                        @foreach ($atlas['districts'] as [$label, $x, $y])
                            <text x="{{ $x }}" y="{{ $y }}">{{ $label }}</text>
                        @endforeach
                        <text class="is-city" x="500" y="690">{{ $atlas['city'] }}</text>
                    </g>

                    {{-- The KOBA trail --}}
                    <path class="atlas__trail" d="{{ $trail }}" pathLength="1"/>
                    <path class="atlas__trail atlas__trail--run" d="{{ $trail }}" pathLength="1"/>
                </svg>

                {{-- Pins (HTML, so they stay crisp and focusable at any zoom) --}}
                @foreach ($branches as $branch)
                    <button class="atlas__pin {{ $loop->first ? 'is-active' : '' }}" type="button" data-atlas-pin="{{ $loop->index }}"
                            style="--x: {{ $branch['pin'][0] / 10 }}%; --y: {{ $branch['pin'][1] / 7 }}%; --d: {{ $loop->index }}"
                            aria-label="{{ $branch['name'] }}">
                        <span class="atlas__pulse" aria-hidden="true"></span>
                        <span class="atlas__marker" aria-hidden="true">
                            <svg viewBox="0 0 40 50"><path d="M20 49C20 49 2 30.5 2 19a18 18 0 0 1 36 0c0 11.5-18 30-18 30z"/></svg>
                            <b>K</b>
                        </span>
                        <span class="atlas__tag" aria-hidden="true">{{ $branch['short'] }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Live Google map, loaded only on request --}}
            <div class="atlas__live" data-atlas-live hidden></div>

            {{-- Controls --}}
            <div class="atlas__mode" role="group" aria-label="{{ $atlas['map_label'] }}">
                <button type="button" class="is-on" data-atlas-mode="art" aria-pressed="true">{{ $atlas['illustrated'] }}</button>
                <button type="button" data-atlas-mode="live" aria-pressed="false" title="{{ $atlas['live_note'] }}">{{ $atlas['live'] }}</button>
            </div>

            <div class="atlas__zoom">
                <button type="button" data-atlas-zoom="1" aria-label="{{ $atlas['zoom_in'] }}">+</button>
                <button type="button" data-atlas-zoom="-1" aria-label="{{ $atlas['zoom_out'] }}">&minus;</button>
                <button type="button" data-atlas-reset aria-label="{{ $atlas['reset'] }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </button>
            </div>

            <svg class="atlas__compass" viewBox="0 0 48 48" aria-hidden="true">
                <circle cx="24" cy="24" r="22"/>
                <path d="M24 7 28 24h-8z" class="is-n"/>
                <path d="M24 41 20 24h8z"/>
                <text x="24" y="5.5">N</text>
            </svg>

            {{-- Floating café card --}}
            <div class="atlas__cards" aria-live="polite">
                @foreach ($branches as $branch)
                    <article class="atlas__card {{ $loop->first ? 'is-active' : '' }}" data-atlas-card="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                        <div class="atlas__thumb">
                            <x-site.img :src="$branch['image']" alt="" sizes="120px" />
                        </div>
                        <div class="atlas__card-body">
                            <p class="atlas__card-mood">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} — {{ $branch['mood'] }}</p>
                            <h4 class="atlas__card-name">{{ $branch['name'] }}</h4>
                            <p class="atlas__status" data-atlas-status hidden></p>
                            <div class="atlas__card-actions">
                                <a href="tel:{{ str_replace(' ', '', $branch['phone']) }}">{{ $atlas['call'] }}</a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($branch['maps']) }}" target="_blank" rel="noopener" data-atlas-embed="https://www.google.com/maps?q={{ urlencode($branch['maps']) }}&output=embed" data-atlas-title="{{ $branch['name'] }}">
                                    {{ $atlas['directions'] }} <x-site.arrow />
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="atlas__note">{{ $atlas['note'] }}</p>
        </div>
    </div>
</section>

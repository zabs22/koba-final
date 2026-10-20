{{-- Guest reviews from Google. Data: config('koba.google_reviews'); behaviour: resources/js/site/reviews.js --}}
@php
    $listings = collect(config('koba.google_reviews.listings', []));
    $mapsUrl = fn (array $l) => $l['maps_url'] ?? 'https://www.google.com/maps/search/?api=1&query='.urlencode($l['maps_query']);
    $own = $listings->flatMap(fn ($l) => collect($l['reviews'])->map(fn ($r) => ['listing' => $l, 'review' => $r]))->values();
    $extra = collect(config('koba.google_reviews.reviews', []))
        ->map(fn ($r) => ['listing' => $listings->firstWhere('key', $r['listing'] ?? null), 'review' => $r])->values();
    // Alternate the two sources so the carousel mixes them.
    $cards = collect();
    foreach (range(0, max($own->count(), $extra->count()) - 1) as $n) {
        foreach ([$own, $extra] as $source) {
            if ($source->has($n)) $cards->push($source[$n]);
        }
    }
@endphp

<section class="vc section is-white" aria-labelledby="voices-title">
    <div class="wrap grid vc__head">
        <div class="vc__heading">
            <p class="eyebrow" data-reveal>{{ __('koba.voices.eyebrow') }}</p>
            <x-site.heading id="voices-title" :text="__('koba.voices.title')" size="l" />
            <p class="lede" data-reveal style="--d: 2">{{ __('koba.voices.lede') }}</p>
        </div>

        @if ($listings->isNotEmpty())
            <div class="vc__ratings" data-reveal style="--d: 3">
                <p class="vc__ratings-title">{{ __('koba.voices.rated') }}</p>
                <ul class="list-reset">
                    @foreach ($listings as $l)
                        <li>
                            <a class="vc__rating" href="{{ $mapsUrl($l) }}" target="_blank" rel="noopener">
                                <span class="vc__rating-name">{{ $l['short'] }}</span>
                                <span class="vc__rating-score">
                                    <span class="vc__stars" style="--r: {{ $l['rating'] }}" aria-hidden="true"></span>
                                    <strong>{{ number_format($l['rating'], 1) }}</strong>
                                    <span class="sr-only">{{ __('koba.voices.rating', ['rating' => number_format($l['rating'], 1)]) }},</span>
                                </span>
                                <span class="vc__rating-count">{{ __('koba.voices.count', ['count' => number_format($l['count'])]) }}</span>
                                <span class="vc__rating-go"><span class="sr-only">{{ __('koba.voices.read_on_google') }} {{ __('koba.voices.new_tab') }}</span><x-site.arrow /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if ($cards->isEmpty())
        <div class="wrap"><p class="vc__empty">{{ __('koba.voices.empty') }}</p></div>
    @else
        <div class="vc__carousel" data-reviews
             data-label-more="{{ __('koba.voices.more') }}" data-label-less="{{ __('koba.voices.less') }}"
             data-label-pause="{{ __('koba.voices.pause') }}" data-label-play="{{ __('koba.voices.play') }}"
             data-label-slide="{{ __('koba.voices.slide') }}">
            <div class="vc__viewport" data-reveal="fade" data-reviews-viewport role="region" aria-roledescription="carousel" aria-label="{{ __('koba.voices.label') }}">
                <ul class="vc__track list-reset" data-reviews-track>
                    @foreach ($cards as $i => $card)
                        @php($l = $card['listing'])
                        @php($r = $card['review'])
                        <li class="vcard" data-review style="--i: {{ $i }}">
                            <div class="vcard__top">
                                <p class="vcard__branch">{{ $l['short'] ?? __('koba.voices.reviewer') }}</p>
                                <x-site.mark class="vcard__mark" />
                            </div>

                            <blockquote class="vcard__quote">
                                <p class="vcard__text{{ mb_strlen($r['text']) > 140 ? ' vcard__text--long' : '' }}" data-review-text>“{{ $r['text'] }}”</p>
                            </blockquote>
                            <button class="vcard__more" type="button" aria-expanded="false" data-review-more hidden>{{ __('koba.voices.more') }}</button>

                            @if (! empty($r['photos']))
                                <div class="vcard__media vcard__media--photos vcard__media--n{{ min(count($r['photos']), 3) }}">
                                    @foreach (array_slice($r['photos'], 0, 3) as [$file, $pw, $ph])
                                        <span class="vcard__ph"><img src="{{ asset('images/koba/reviews/'.$file) }}" width="{{ $pw }}" height="{{ $ph }}" alt="{{ __('koba.voices.photo', ['name' => $r['author'] ?? __('koba.voices.reviewer')]) }}" loading="lazy" decoding="async" draggable="false"></span>
                                    @endforeach
                                </div>
                            @elseif (! empty($l['image']))
                                <div class="vcard__media" aria-hidden="true">
                                    <x-site.img :src="$l['image']" alt="" sizes="(min-width: 900px) 40vw, 84vw" draggable="false" />
                                </div>
                            @endif

                            <footer class="vcard__foot">
                                <p class="vcard__who">
                                    <span>{{ $r['author'] ?? __('koba.voices.reviewer') }}</span>
                                    @if (! empty($r['date']))<span> · {{ $r['date'] }}</span>@endif
                                </p>
                                <p class="vcard__rate">
                                    @if (! empty($r['rating']))
                                        <span class="vc__stars" style="--r: {{ $r['rating'] }}" role="img" aria-label="{{ __('koba.voices.rating', ['rating' => number_format($r['rating'], 1)]) }}"></span>
                                        <strong>{{ number_format($r['rating'], 1) }}</strong>
                                    @else
                                        <span class="vc__stars" style="--r: {{ $l['rating'] }}" aria-hidden="true"></span>
                                        <span>{{ __('koba.voices.branch_rating') }} <strong>{{ number_format($l['rating'], 1) }}</strong> · {{ __('koba.voices.count', ['count' => number_format($l['count'])]) }}</span>
                                    @endif
                                </p>
                                @if ($l)
                                    <a class="link" href="{{ $mapsUrl($l) }}" target="_blank" rel="noopener">{{ __('koba.voices.view_on_maps') }}<span class="sr-only"> — {{ $l['short'] }} {{ __('koba.voices.new_tab') }}</span> <x-site.arrow /></a>
                                @endif
                            </footer>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="wrap vc__controls" data-reveal="fade" @if ($cards->count() < 2) hidden @endif>
                <p class="vc__count" aria-hidden="true"><span data-reviews-current>01</span> <span class="vc__count-sep"></span> <span>{{ str_pad($cards->count(), 2, '0', STR_PAD_LEFT) }}</span></p>
                <div class="vc__progress" aria-hidden="true"><span data-reviews-progress style="--p: {{ round(1 / max(1, $cards->count()), 3) }}"></span></div>
                <div class="vc__buttons">
                    <button class="round-btn vc__pause" type="button" data-reviews-pause aria-pressed="false" aria-label="{{ __('koba.voices.pause') }}" hidden>
                        <svg class="vc__pause-on" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="0.5" /><rect x="14" y="5" width="4" height="14" rx="0.5" /></svg>
                        <svg class="vc__pause-off" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M7 5l12 7-12 7z" /></svg>
                    </button>
                    <button class="round-btn" type="button" data-reviews-prev aria-label="{{ __('koba.ui.previous') }}"><x-site.arrow class="flip" /></button>
                    <button class="round-btn" type="button" data-reviews-next aria-label="{{ __('koba.ui.next') }}"><x-site.arrow /></button>
                </div>
            </div>
            <p class="sr-only" aria-live="polite" data-reviews-status></p>
        </div>

        <div class="wrap"><p class="vc__note" data-reveal="fade">{{ __('koba.voices.note') }}</p></div>
    @endif
</section>

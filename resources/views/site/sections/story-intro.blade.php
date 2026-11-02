{{-- Our Story, told quietly: one line that inks in word by word as you read down the page, with
     small photographs set into it, then three facts that open onto a photograph.
     Behaviour: resources/js/site/home.js → initStory --}}
@php
    $copy = trans('koba.story_intro');
    // Each word (or [image-key]) becomes one step of the reading.
    $tokens = collect(preg_split('/\s+/u', trim($copy['statement'])))->map(function ($token) {
        if (preg_match('/^\[([a-z0-9-]+)\]$/', $token, $m)) return ['image' => $m[1]];
        $em = str_starts_with($token, '*');
        return ['word' => trim($token, '*'), 'em' => $em];
    });
@endphp

<section class="story-intro" id="intro" aria-labelledby="story-intro-title" data-story data-chapter="{{ __('koba.chapters.story') }}">
    <x-site.leaf class="story-intro__leaf" aria-hidden="true" />

    <div class="wrap">
        <header class="story-intro__label" data-reveal>
            <h2 id="story-intro-title" class="eyebrow">{{ $copy['eyebrow'] }}</h2>
            <span class="story-intro__est">{{ $copy['founded'] }}</span>
            <a class="link story-intro__cta" href="{{ route('our-story') }}">{{ $copy['cta'] }} <x-site.arrow /></a>
        </header>

        <p class="story-intro__statement" data-story-statement style="--n: {{ $tokens->count() }}">
            @foreach ($tokens as $i => $token)
                @if (isset($token['image']))
                    <span class="story-intro__pic" style="--i: {{ $i }}" aria-hidden="true"><x-site.img :src="$token['image']" sizes="320px" alt="" /></span>
                @else
                    <span class="story-intro__w {{ $token['em'] ? 'is-em' : '' }}" style="--i: {{ $i }}">{{ $token['word'] }}</span>
                @endif
            @endforeach
        </p>

        <p class="story-intro__lede" data-reveal>{{ $copy['lede'] }}</p>

        <dl class="story-intro__facts" aria-label="{{ $copy['facts_label'] }}">
            @foreach ($copy['facts'] as $i => $fact)
                <div class="story-intro__fact" tabindex="0" data-reveal style="--d: {{ $i + 1 }}">
                    <x-site.img :src="$fact['image']" sizes="(min-width: 900px) 30vw, 92vw" alt="" aria-hidden="true" />
                    <dt @isset($fact['count']) data-count="{{ $fact['count'] }}" data-count-from="{{ $fact['from'] }}" data-count-suffix="{{ $fact['suffix'] ?? '' }}" @endisset>{{ $fact['value'] }}</dt>
                    <dd>{{ $fact['label'] }}</dd>
                    @isset($fact['body']) <dd class="story-intro__body">{{ $fact['body'] }}</dd> @endisset
                </div>
            @endforeach
        </dl>
    </div>
</section>

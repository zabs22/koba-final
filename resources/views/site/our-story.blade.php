<x-site.layout page="our-story" :title="__('koba.meta.pages.our-story')" :description="__('koba.story.lede')" image="craft-crumb">
    @include('site.partials.page-hero', [
        'eyebrow' => __('koba.story.eyebrow'),
        'title' => __('koba.story.title'),
        'lede' => __('koba.story.lede'),
        'slides' => [['croissant', '50% 55%'], ['craft-almond', '50% 45%'], ['craft-crumb', '50% 50%'], ['opera-slice', '50% 60%']],
    ])

    {{-- The pour: a cup filled as you scroll (resources/js/site/pour.js) --}}
    @include('site.sections.pour')

    {{-- The story in three moments: panels that open on hover, focus or tap (resources/js/site/story.js) --}}
    @php($story = trans('koba.story'))
    <section class="moments section is-white" aria-labelledby="moments-title" data-moments>
        <div class="wrap">
            <header class="moments__head">
                <p class="eyebrow" data-reveal>{{ $story['moments_eyebrow'] }}</p>
                <x-site.heading id="moments-title" :text="$story['moments_title']" size="l" />
            </header>
            <ol class="list-reset moments__list">
                @foreach ($story['moments'] as $i => $moment)
                    <li class="moment {{ $i === 0 ? 'is-active' : '' }}" data-moment data-reveal style="--d: {{ $i }}">
                        <button class="moment__tab" type="button" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="moment-{{ $i }}">
                            <span class="moment__no">{{ $moment['no'] }}</span>
                            <span class="moment__label">{{ $moment['label'] }}</span>
                        </button>
                        <div class="moment__media" aria-hidden="true">
                            <x-site.img :src="$moment['image']" sizes="(min-width: 900px) 50vw, 92vw" :position="$moment['position'] ?? '50% 50%'" alt="" />
                        </div>
                        <div class="moment__body" id="moment-{{ $i }}">
                            <h3 class="moment__title">{{ $moment['title'] }}</h3>
                            <p class="moment__line">{{ $moment['line'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- About us: what KOBA makes, and the bakery that never closes --}}
    <section class="about-us section"aria-labelledby="about-us-title">
        <div class="wrap grid about-us__grid">
            <div class="about-us__copy">
                <p class="eyebrow" data-reveal>{{ $story['about_eyebrow'] }}</p>
                <x-site.heading id="about-us-title" :text="$story['about_title']" size="l" />
                <p class="lede" data-reveal style="--d: 2">{{ $story['about_body'] }}</p>
                <p class="about-us__hours" data-reveal style="--d: 3">
                    <span class="about-us__hours-value display">{{ $story['about_hours'] }}</span>
                    <span class="about-us__hours-line">{{ $story['about_hours_line'] }}</span>
                </p>
            </div>
            <ul class="list-reset about-us__makes">
                @foreach ($story['about_makes'] as $make)
                    <li class="about-us__make" data-reveal style="--d: {{ $loop->index }}">
                        <div class="about-us__photo">
                            <x-site.img :src="$make['image']" sizes="(min-width: 900px) 22vw, 45vw" alt="" />
                        </div>
                        <h3 class="about-us__name">{{ $make['name'] }}</h3>
                        <p class="about-us__line">{{ $make['line'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- What we value: three details beside the koba leaf mark, which draws itself in --}}
    <section class="details section is-cream-2" aria-labelledby="details-title">
        <div class="wrap grid details__grid">
            <div class="details__copy">
                <p class="eyebrow" data-reveal>{{ $story['details_eyebrow'] }}</p>
                <x-site.heading id="details-title" :text="$story['details_title']" size="l" class="details__title" />
                <ol class="list-reset details__list">
                    @foreach ($story['details'] as $detail)
                        <li class="detail" data-reveal style="--d: {{ $loop->index }}">
                            <span class="detail__no" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="detail__name">{{ $detail['name'] }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="details__close" data-reveal>{{ $story['closing'] }}</p>
            </div>
            <div class="details__mark" data-reveal="draw">
                <x-site.mark />
            </div>
        </div>
    </section>

    {{-- The collections: signature and seasonal, side by side --}}
    <section class="collections section is-white" aria-labelledby="collections-title">
        <div class="wrap">
            <header class="collections__head">
                <p class="eyebrow" data-reveal>{{ $story['collections_eyebrow'] }}</p>
                <x-site.heading id="collections-title" :text="$story['collections_title']" size="l" />
            </header>
            <div class="collections__list">
                @foreach ($story['collections'] as $collection)
                    <article class="collection" data-reveal style="--d: {{ $loop->index }}">
                        <a class="collection__photo" href="{{ route($collection['route']) }}" tabindex="-1" aria-hidden="true">
                            <x-site.img :src="$collection['image']" sizes="(min-width: 900px) 45vw, 92vw" alt="" />
                        </a>
                        <div class="collection__body">
                            <span class="collection__no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="collection__name">{{ $collection['name'] }}</h3>
                            <p class="collection__text">{{ $collection['body'] }}</p>
                            <a class="link" href="{{ route($collection['route']) }}">{{ $collection['cta'] }} <x-site.arrow /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- @include('site.partials.our-story-classic') --}}

    @include('site.sections.final-cta')
</x-site.layout>

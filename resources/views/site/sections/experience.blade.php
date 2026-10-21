<section class="experience" aria-labelledby="experience-title" data-scrub data-chapter="{{ __('koba.chapters.experience') }}" data-chapter-tone="dark">
    <div class="experience__sticky">
        <div class="experience__frame media" data-reveal="media">
            <x-site.img src="drink-iced-latte-terrace" sizes="100vw" position="50% 46%" />
        </div>

        {{-- Brand leaves part like a curtain as the photograph opens. --}}
        <div class="experience__curtain" aria-hidden="true">
            <x-site.leaf class="experience__leaf experience__leaf--1" />
            <x-site.leaf class="experience__leaf experience__leaf--2" />
            <x-site.leaf class="experience__leaf experience__leaf--3" />
            <x-site.leaf class="experience__leaf experience__leaf--4" />
        </div>

        <div class="wrap experience__copy is-dark">
            <p class="eyebrow">{{ __('koba.experience.eyebrow') }}</p>
            <x-site.heading id="experience-title" :text="__('koba.experience.title')" size="xl" />
            <p class="lede">Coffee, pastry, good company. Stay awhile.</p>
            <a class="btn btn--cream" href="{{ route('experience') }}">{{ __('koba.experience.cta') }} <x-site.arrow /></a>
        </div>
    </div>
</section>

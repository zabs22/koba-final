<x-site.layout page="home">
    @include('site.sections.hero')
    <div class="home-flow" data-home>
        @include('site.sections.intro-classic')
        {{-- @include('site.sections.creations') --}}
        @include('site.sections.pillars')
        @include('site.sections.craft')
        @include('site.sections.menu-preview')
        @include('site.sections.cakes-feature')
        @include('site.sections.experience')
        @include('site.sections.manifesto')
        @include('site.sections.locations')
        @include('site.sections.voices')

        {{-- Chapter guide: where you are in the story. Built from [data-chapter] by home.js. --}}
        <nav class="chapters" aria-label="{{ __('koba.chapters.label') }}" data-chapters hidden>
            <ol class="list-reset chapters__list" data-chapters-list></ol>
            <p class="chapters__now" aria-hidden="true"><span data-chapters-no>01</span><b data-chapters-name></b></p>
        </nav>
    </div>
    @include('site.sections.final-cta')
</x-site.layout>

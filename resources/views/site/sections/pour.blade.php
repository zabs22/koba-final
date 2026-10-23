{{--
    The pour: between the hero and the chapters, one cup is filled as you scroll —
    empty cup → the pot arrives → the pour → the aroma → the cup, ready.

    Every layer is cut from one photograph and shares its 764 × 1024 frame
    (public/images/koba/ritual). resources/js/site/pour.js drives the layers and the
    WebGL steam; resources/css/site/pour.css places them. Without JS, or with reduced
    motion, the frame rests mid-pour beside all five steps.
--}}
@php
    $steps = trans('koba.story.pour.steps');
    $img = fn ($name) => asset("images/koba/ritual/ritual-$name.webp");
@endphp

<section class="pour" data-pour aria-labelledby="pour-title" style="--steps: {{ count($steps) }}">
    <div class="pour__stage" data-pour-stage>
        {{-- The room continues past the photograph: the same frame, out of focus --}}
        <div class="pour__backdrop" aria-hidden="true">
            <img src="{{ $img('room') }}" alt="" width="191" height="256" decoding="async">
        </div>
        <div class="pour__shade" aria-hidden="true"></div>
        <span class="pour__ghost ethiopic" aria-hidden="true">ቡና</span>

        <div class="pour__window">
            <div class="pour__frame" data-pour-frame>
                <img class="pour__base" src="{{ $img('empty') }}" alt="{{ __('koba.story.pour.alt') }}" width="764" height="1024" decoding="async">
                <div class="pour__glow" data-pour-glow aria-hidden="true"></div>

                {{-- The coffee rises inside the rim: the liquid slides up behind the cup's front wall --}}
                <div class="pour__cup" aria-hidden="true">
                    <div class="pour__liquid" data-pour-liquid>
                        <img class="pour__coffee" src="{{ $img('coffee') }}" alt="" width="275" height="74" decoding="async">
                        <img class="pour__coffee pour__coffee--still" src="{{ $img('coffee-still') }}" alt="" width="275" height="74" decoding="async" data-pour-still>
                        <svg class="pour__ripples" viewBox="0 0 275 74" preserveAspectRatio="none" focusable="false">
                            <ellipse cx="132" cy="48" rx="44" ry="9" />
                            <ellipse cx="132" cy="48" rx="44" ry="9" />
                            <ellipse cx="132" cy="48" rx="44" ry="9" />
                        </svg>
                    </div>
                </div>

                <img class="pour__steam-photo" src="{{ $img('steam') }}" alt="" width="302" height="503" decoding="async" data-pour-vapour aria-hidden="true">

                {{-- Pot and stream share one pivot (the hand at the handle), so the stream stays on the spout --}}
                <div class="pour__hand" aria-hidden="true">
                    <div class="pour__stream" data-pour-stream style="--src: url('{{ $img('stream') }}')">
                        <img src="{{ $img('stream') }}" alt="" width="102" height="349" decoding="async">
                    </div>
                    <div class="pour__pot" data-pour-pot>
                        <img src="{{ $img('pot') }}" alt="" width="334" height="417" decoding="async">
                    </div>
                </div>

                <canvas class="pour__steam" data-pour-steam aria-hidden="true"></canvas>
                <span class="pour__motes" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
            </div>
        </div>

        <div class="wrap pour__inner">
            <div class="pour__copy">
                <p class="eyebrow pour__eyebrow"><span class="ethiopic" lang="am">ቡና</span>&nbsp;·&nbsp;{{ __('koba.story.pour.eyebrow') }}</p>
                <h2 id="pour-title" class="sr-only">{{ __('koba.story.pour.title') }}</h2>

                <ol class="list-reset pour__steps">
                    @foreach ($steps as $i => $step)
                        <li class="pour__step {{ $i === 0 ? 'is-current' : '' }}" data-pour-step>
                            <p class="pour__label index"><span>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $step['label'] }}</p>
                            <h3 class="display pour__title">
                                @foreach (explode('|', $step['title']) as $l => $line)
                                    <span class="line"><span style="--l: {{ $l }}">{!! preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($line)) !!}</span></span>
                                @endforeach
                            </h3>
                            <p class="pour__body">{{ $step['body'] }}</p>
                            @if ($loop->last)
                                <p class="pour__cue index"><x-site.mark /> {{ __('koba.story.pour.cue') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <div class="pour__meter" aria-hidden="true">
                    <span class="pour__count index"><b data-pour-count>01</b>&nbsp;/&nbsp;{{ str_pad(count($steps), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="pour__bar"><i></i></span>
                </div>
            </div>
        </div>

        <ol class="list-reset pour__rail" aria-hidden="true">
            @foreach ($steps as $step)
                <li class="pour__tick {{ $loop->first ? 'is-current' : '' }}" data-pour-tick><span>{{ $step['label'] }}</span><i></i></li>
            @endforeach
        </ol>

        <button class="pour__skip" type="button" data-pour-skip>
            {{ __('koba.story.pour.skip') }}
            <x-site.arrow class="pour__skip-icon" />
        </button>
    </div>
</section>

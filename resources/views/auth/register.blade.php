<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <p class="text-[11px] font-semibold tracking-[0.18em] text-koba-brown uppercase">Join KOBA</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-koba-900">Create your account</h1>
        <p class="mt-2 text-sm text-koba-900/65">Pre-order from any KOBA café, skip the queue and keep your favourites in one place.</p>

        <x-validation-errors class="mt-6 rounded-lg bg-red-50 p-4" />

        <form method="POST" action="{{ route('register') }}" class="mt-8">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="mt-5">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                </div>
                <div>
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                    <x-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <x-label for="terms">
                        <div class="flex items-center">
                            <x-checkbox name="terms" id="terms" required />

                            <div class="ms-2">
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </div>
                        </div>
                    </x-label>
                </div>
            @endif

            <x-button class="mt-7 w-full">
                {{ __('Register') }}
            </x-button>
        </form>

        <p class="mt-8 border-t border-koba-900/10 pt-6 text-center text-sm text-koba-900/65">
            {{ __('Already registered?') }}
            <a class="font-semibold text-koba-700 hover:text-koba-900 hover:underline" href="{{ route('login') }}">Sign in</a>
        </p>
    </x-authentication-card>
</x-guest-layout>

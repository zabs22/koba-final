<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <p class="text-[11px] font-semibold tracking-[0.18em] text-koba-brown uppercase">Welcome back</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-koba-900">Sign in to KOBA</h1>
        <p class="mt-2 text-sm text-koba-900/65">Follow your pre-orders, reorder favourites — or run your café.</p>

        <x-validation-errors class="mt-6 rounded-lg bg-red-50 p-4" />

        @session('status')
            <div class="mt-6 rounded-lg bg-koba-600/10 p-4 text-sm font-medium text-koba-700">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="mt-8">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between">
                    <x-label for="password" value="{{ __('Password') }}" />
                    @if (Route::has('password.request'))
                        <a class="text-[13px] font-medium text-koba-700 hover:text-koba-900 hover:underline" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
                <x-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <label for="remember_me" class="mt-5 flex items-center">
                <x-checkbox id="remember_me" name="remember" />
                <span class="ms-2 text-sm text-koba-900/70">{{ __('Remember me') }}</span>
            </label>

            <x-button class="mt-7 w-full">
                {{ __('Log in') }}
            </x-button>
        </form>

        @if (Route::has('register'))
            <p class="mt-8 border-t border-koba-900/10 pt-6 text-center text-sm text-koba-900/65">
                New to KOBA pre-ordering?
                <a href="{{ route('register') }}" class="font-semibold text-koba-700 hover:text-koba-900 hover:underline">Create an account</a>
            </p>
        @endif
    </x-authentication-card>
</x-guest-layout>

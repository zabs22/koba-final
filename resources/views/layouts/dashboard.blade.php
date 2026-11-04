{{-- Base document for every dashboard (admin, branch, customer). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#062e22">

    <title>@yield('title', 'Dashboard') · KOBA</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400..700&family=Montserrat:wght@500..700&family=Noto+Sans+Ethiopic:wght@400..600&display=swap">

    {{-- Theme before first paint (light / dark / system, remembered in this browser). --}}
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('koba:theme') || 'system';
                if (mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>

    @livewireScriptConfig
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
</head>
<body class="h-full bg-canvas font-sans text-sm text-ink antialiased">
    <a href="#main" class="sr-only z-50 rounded-lg bg-panel px-3 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    @yield('body')

    @include('dashboard.partials.toasts')
    @include('dashboard.partials.confirm')
    @auth
        @include('dashboard.partials.command-palette')
    @endauth
</body>
</html>

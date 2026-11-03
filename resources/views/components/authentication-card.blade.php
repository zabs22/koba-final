{{-- KOBA sign-in frame: brand photograph and promise on the left (desktop), the form on cream. --}}
<div class="min-h-screen bg-koba-cream lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
    <aside class="relative hidden overflow-hidden bg-koba-900 lg:block" aria-hidden="true">
        <img src="{{ asset('images/koba/croissant-1400.webp') }}" alt="" class="absolute inset-0 size-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-t from-koba-950/95 via-koba-950/30 to-koba-950/10"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-koba-cream">
            <a href="{{ route('home') }}" class="block w-40"><x-site.logo variant="sand" class="h-auto w-full" /></a>
            <div class="max-w-md">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-koba-sand uppercase">Pre-order · Pick up · Enjoy</p>
                <p class="mt-3 font-display text-[40px] leading-[1.08] font-semibold tracking-tight">Your KOBA,<br>ready when you arrive.</p>
                <p class="mt-4 text-sm text-koba-cream/70">Order ahead from Sanford, Bole Atlas or 4 Killo and follow it from the oven to the counter.</p>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12 sm:px-10">
        <div class="mb-10 lg:hidden">{{ $logo }}</div>
        <div class="w-full max-w-[400px]">
            {{ $slot }}
        </div>
        <p class="mt-10 text-xs text-koba-900/50">© {{ date('Y') }} KOBA Patisserie & Bakery · Addis Ababa</p>
    </div>
</div>

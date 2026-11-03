@extends($layout)

@section('title', 'Profile')

@section('content')
{{-- Posts to Fortify's own endpoints (same validation and hashing as Jetstream's profile page). --}}
<x-dashboard.page-header eyebrow="Account" title="Your profile" description="Your name, email and password. Two-factor authentication and browser sessions are on the security page." />

<div class="max-w-3xl space-y-5">
    @if (session('status') === 'profile-information-updated' || session('status') === 'password-updated')
        <div class="flex items-center gap-2 rounded-xl bg-success-soft px-4 py-3 text-[13px] text-success" x-data x-init="setTimeout(() => $el.remove(), 5000)">
            <x-dashboard.icon name="circle-check" /> {{ session('status') === 'password-updated' ? 'Password changed.' : 'Profile saved.' }}
        </div>
    @endif

    <x-dashboard.card title="Profile information">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-full bg-forest-900 font-display text-lg font-semibold text-sand-300">{{ \App\Services\Demo\DemoCatalog::initials($user->name) }}</span>
                <div><p class="font-medium">{{ $user->name }}</p><p class="text-xs text-ink-faint">{{ $user->role()->label() }}@if ($user->branchKey()) · {{ app(\App\Services\Demo\DemoDataService::class)->branch($user->branchKey())['name'] }}@endif</p></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="p-name">Name</label>
                    <input id="p-name" name="name" class="field" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    @error('name', 'updateProfileInformation')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="p-email">Email</label>
                    <input id="p-email" type="email" name="email" class="field" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email', 'updateProfileInformation')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
            </div>
            @if (in_array($user->email, array_keys(config('dashboard.accounts')), true))
                <p class="text-xs text-ink-faint">This demo account's role comes from its email — changing the email turns it into a customer account.</p>
            @endif
            <div class="flex justify-end"><button class="btn-primary">Save</button></div>
        </form>
    </x-dashboard.card>

    <x-dashboard.card title="Change password">
        <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                    <div>
                        <label class="label" for="p-{{ $field }}">{{ $label }}</label>
                        <input id="p-{{ $field }}" type="password" name="{{ $field }}" class="field" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required>
                        @error($field, 'updatePassword')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end"><button class="btn-primary">Update password</button></div>
        </form>
    </x-dashboard.card>

    <a href="{{ route('profile.show') }}" class="card flex items-center gap-4 p-5 transition-colors hover:border-line-strong">
        <span class="grid size-10 place-items-center rounded-xl bg-accent-soft text-accent"><x-dashboard.icon name="shield-check" /></span>
        <div class="flex-1"><p class="font-medium">Security</p><p class="text-xs text-ink-soft">Two-factor authentication, passkeys, browser sessions and account deletion.</p></div>
        <x-dashboard.icon name="chevron-right" class="text-ink-faint" />
    </a>
</div>
@endsection

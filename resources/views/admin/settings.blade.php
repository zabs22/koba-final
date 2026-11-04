@extends('layouts.admin')

@section('title', 'Platform settings')

@section('content')
<x-dashboard.page-header eyebrow="System" title="Platform settings" description="Defaults for every café. Branches can adjust their own hours and prep targets." />

<form x-data="remoteForm({ url: @js(route('admin.settings.update')), values: @js($settings) })" @submit.prevent="submit()" class="max-w-3xl space-y-5">
    <x-dashboard.card title="Platform" subtitle="How KOBA pre-ordering presents itself">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="s-name">Platform name</label><input id="s-name" class="field" x-model="values.platform_name" required maxlength="60"></div>
            <div><label class="label" for="s-email">Support email</label><input id="s-email" type="email" class="field" x-model="values.support_email" required></div>
            <div>
                <label class="label" for="s-currency">Currency</label>
                <select id="s-currency" class="field" x-model="values.currency"><option value="ETB">ETB — Ethiopian Birr</option></select>
            </div>
        </div>
    </x-dashboard.card>

    <x-dashboard.card title="Commission" subtitle="KOBA's share of each pre-order; the rest is paid out to the branch">
        <div class="flex items-center gap-4">
            <input type="range" min="0" max="30" step="0.5" x-model.number="values.commission" class="flex-1 accent-forest-700 dark:accent-sand-400" aria-label="Commission percentage">
            <div class="flex w-28 items-center gap-1"><input type="number" min="0" max="30" step="0.5" class="field text-right" x-model.number="values.commission" aria-label="Commission %"><span class="text-ink-soft">%</span></div>
        </div>
        <p class="mt-3 text-xs text-ink-soft">On an order of ETB 500: platform <span class="font-medium tabular-nums text-ink" x-text="$fmt(500 * values.commission / 100, 'etb')"></span>, branch <span class="font-medium tabular-nums text-ink" x-text="$fmt(500 - 500 * values.commission / 100, 'etb')"></span>.</p>
    </x-dashboard.card>

    <x-dashboard.card title="Operating defaults" subtitle="Used for new branches">
        <div class="grid gap-4 sm:grid-cols-4">
            <div><label class="label" for="s-opens">Opens</label><input id="s-opens" type="time" class="field" x-model="values.opens" required></div>
            <div><label class="label" for="s-closes">Closes</label><input id="s-closes" type="time" class="field" x-model="values.closes" required></div>
            <div><label class="label" for="s-prep">Prep target (min)</label><input id="s-prep" type="number" min="5" max="60" class="field" x-model.number="values.prep_target" required></div>
            <div>
                <label class="label" for="s-slot">Pickup slot</label>
                <select id="s-slot" class="field" x-model.number="values.slot_minutes">@foreach ([10, 15, 20, 30] as $m)<option value="{{ $m }}">{{ $m }} min</option>@endforeach</select>
            </div>
        </div>
    </x-dashboard.card>

    <div class="flex items-center justify-end gap-3">
        <p class="text-xs text-ink-faint">Demo: saved to the demo store.</p>
        <button class="btn-primary" :disabled="saving"><x-dashboard.icon name="check" /> Save settings</button>
    </div>
</form>
@endsection

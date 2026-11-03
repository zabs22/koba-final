@extends('layouts.branch')

@section('title', 'Branch settings')

@section('content')
<x-dashboard.page-header eyebrow="Branch" title="Branch settings" description="Opening hours, kitchen targets and how pickups are spaced for this café." />

<form x-data="remoteForm({ url: @js(route('branch.settings.update')), values: @js($settings) })" @submit.prevent="submit()" class="max-w-3xl space-y-5">
    <x-dashboard.card title="Opening hours" subtitle="Pre-orders can only be picked up while the café is open">
        <div class="grid gap-4 sm:grid-cols-2">
            <fieldset class="rounded-lg border border-line p-4">
                <legend class="px-1 text-[13px] font-medium">Monday – Friday</legend>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label" for="h-o">Opens</label><input id="h-o" type="time" class="field" x-model="values.opens" required></div>
                    <div><label class="label" for="h-c">Closes</label><input id="h-c" type="time" class="field" x-model="values.closes" required></div>
                </div>
            </fieldset>
            <fieldset class="rounded-lg border border-line p-4">
                <legend class="px-1 text-[13px] font-medium">Saturday & Sunday</legend>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label" for="h-wo">Opens</label><input id="h-wo" type="time" class="field" x-model="values.weekend_opens" required></div>
                    <div><label class="label" for="h-wc">Closes</label><input id="h-wc" type="time" class="field" x-model="values.weekend_closes" required></div>
                </div>
            </fieldset>
        </div>
    </x-dashboard.card>

    <x-dashboard.card title="Kitchen & pickups">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="label" for="k-prep">Prep target (min)</label><input id="k-prep" type="number" min="5" max="60" class="field" x-model.number="values.prep_target" required></div>
            <div>
                <label class="label" for="k-slot">Pickup slot length</label>
                <select id="k-slot" class="field" x-model.number="values.slot_minutes">@foreach ([10, 15, 20, 30] as $m)<option value="{{ $m }}">{{ $m }} minutes</option>@endforeach</select>
            </div>
            <div><label class="label" for="k-max">Max pickups per slot</label><input id="k-max" type="number" min="1" max="60" class="field" x-model.number="values.max_per_slot" required></div>
        </div>
        <div class="mt-5 flex items-start justify-between gap-4 rounded-lg bg-subtle p-4 ring-1 ring-line">
            <div><p class="text-[13px] font-medium">Accept new orders automatically</p><p class="text-xs text-ink-soft">Orders go straight to “Preparing” instead of waiting in “New”.</p></div>
            <x-dashboard.toggle model="values.auto_accept" label="Accept new orders automatically" @click="values.auto_accept = !values.auto_accept" />
        </div>
    </x-dashboard.card>

    <x-dashboard.card title="Contact">
        <div class="max-w-xs"><label class="label" for="c-phone">Branch phone (shown to customers)</label><input id="c-phone" class="field" x-model="values.phone" required></div>
    </x-dashboard.card>

    <div class="flex items-center justify-end gap-3">
        <p class="text-xs text-ink-faint">Demo: saved to the demo store.</p>
        <button class="btn-primary" :disabled="saving"><x-dashboard.icon name="check" /> Save settings</button>
    </div>
</form>
@endsection

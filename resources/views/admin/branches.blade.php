@extends('layouts.admin')

@section('title', 'Branches')

@section('content')
<div x-data="{
        open: false, editing: null, saving: false, form: {},
        create() { this.editing = null; this.form = { name: '', short: '', area: '', phone: '', manager: '', status: 'open' }; this.open = true },
        edit(row) { this.editing = row.key; this.form = { name: row.name, area: row.area, phone: row.phone, manager: row.manager ?? '', status: row.status }; this.open = true },
        async save() {
            this.saving = true;
            const url = this.editing ? @js(route('admin.branches.update', '__KEY__')).replace('__KEY__', this.editing) : @js(route('admin.branches.store'));
            const data = await this.$action(url, this.form, this.editing ? 'PUT' : 'POST');
            this.saving = false;
            if (data) { window.dispatchEvent(new CustomEvent('table:rows', { detail: data.rows })); this.open = false }
        },
     }">
    <x-dashboard.page-header eyebrow="Operations" title="Branches" description="KOBA's cafés, their status and the last 30 days. Pausing a branch stops new pre-orders immediately.">
        <x-slot:actions>
            <button type="button" class="btn-primary" @click="create()"><x-dashboard.icon name="plus" /> New branch</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    {{-- Branch cards --}}
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        @foreach (array_slice($rows, 0, 3) as $b)
            <a href="{{ route('admin.branches.show', $b['key']) }}" class="card group flex items-center gap-4 p-4 transition-colors hover:border-line-strong">
                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-forest-900 text-sand-300"><x-dashboard.icon name="store" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 font-display text-[15px] font-semibold">{{ $b['short'] }}</p>
                    <p class="truncate text-xs text-ink-faint">{{ $b['area'] }} · {{ $b['active'] }} active now</p>
                </div>
                <div class="text-right">
                    <p class="font-display text-[15px] font-semibold tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($b['revenue'], 0) }}</p>
                    <p class="text-xs text-ink-faint">30 days</p>
                </div>
            </a>
        @endforeach
    </div>

    <x-dashboard.data-table
        :rows="$rows" :searchable="['name', 'area', 'manager']" search="Search branches…" sort="revenue" export-name="branches"
        :filters="['status' => ['label' => 'Status', 'options' => ['open' => 'Open', 'paused' => 'Paused', 'closed' => 'Closed']]]"
        :columns="[
            ['key' => 'name', 'label' => 'Branch', 'type' => 'stack', 'sub' => 'area', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ['key' => 'manager', 'label' => 'Manager', 'type' => 'muted'],
            ['key' => 'orders', 'label' => 'Orders', 'type' => 'number', 'sortable' => true, 'align' => 'right'],
            ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'money', 'sortable' => true, 'align' => 'right'],
            ['key' => 'prep', 'label' => 'Avg. prep', 'type' => 'minutes', 'sortable' => true, 'align' => 'right'],
            ['key' => 'rating', 'label' => 'Rating', 'type' => 'rating', 'sortable' => true],
            ['key' => 'key', 'label' => '', 'type' => 'actions'],
        ]">
        <x-slot:row>
            <tr>
                <td><a :href="@js(route('admin.branches')) + '/' + row.key" class="block font-medium hover:underline" x-text="row.name"></a><span class="text-xs text-ink-faint" x-text="row.area + ' · ' + row.phone"></span></td>
                <td x-html="$badge(row.status)"></td>
                <td class="text-ink-soft" x-text="row.manager ?? '—'"></td>
                <td class="text-right tabular-nums" x-text="$fmt(row.orders)"></td>
                <td class="text-right font-medium tabular-nums" x-text="$fmt(row.revenue, 'etb')"></td>
                <td class="text-right tabular-nums" x-text="row.prep ? row.prep + ' min' : '—'"></td>
                <td x-html="render(columns[6], row)"></td>
                <td class="text-right whitespace-nowrap">
                    <a :href="@js(route('admin.branches')) + '/' + row.key" class="btn-ghost btn-sm">Analytics</a>
                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('row-action', { action: 'edit', row })"><x-dashboard.icon name="pencil" class="size-3.5" /> Edit</button>
                </td>
            </tr>
        </x-slot:row>
        <x-slot:card>
            <li class="flex items-center gap-3 px-4 py-3.5">
                <div class="min-w-0 flex-1">
                    <a :href="@js(route('admin.branches')) + '/' + row.key" class="flex items-center gap-2 font-medium"><span x-text="row.short"></span><span x-html="$badge(row.status)"></span></a>
                    <p class="text-xs text-ink-faint" x-text="$fmt(row.orders) + ' orders · ' + $fmt(row.revenue, 'etb')"></p>
                </div>
                <button type="button" class="btn-secondary btn-sm" @click="$dispatch('row-action', { action: 'edit', row })">Edit</button>
            </li>
        </x-slot:card>
    </x-dashboard.data-table>

    <div @row-action.window="$event.detail.action === 'edit' && edit($event.detail.row)"></div>

    {{-- Create / edit --}}
    <x-dashboard.slide-over show="open" close="open = false">
        <form class="flex h-full flex-col" @submit.prevent="save()">
            <header class="border-b border-line px-5 py-4">
                <p class="eyebrow" x-text="editing ? 'Edit branch' : 'New branch'"></p>
                <h2 class="mt-0.5 font-display text-base font-semibold" x-text="editing ? form.name : 'Add a KOBA café'"></h2>
            </header>
            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                <div><label class="label" for="b-name">Full name</label><input id="b-name" class="field" x-model="form.name" required maxlength="80" placeholder="KOBA Kazanchis"></div>
                <div x-show="!editing"><label class="label" for="b-short">Short name</label><input id="b-short" class="field" x-model="form.short" :required="!editing" maxlength="40" placeholder="Kazanchis"></div>
                <div><label class="label" for="b-area">Area</label><input id="b-area" class="field" x-model="form.area" required maxlength="80" placeholder="Kazanchis, Addis Ababa"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label" for="b-phone">Phone</label><input id="b-phone" class="field" x-model="form.phone" required maxlength="30" placeholder="+251 9…"></div>
                    <div><label class="label" for="b-manager">Manager</label><input id="b-manager" class="field" x-model="form.manager" maxlength="80"></div>
                </div>
                <fieldset>
                    <legend class="label">Status</legend>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['open' => 'Open', 'paused' => 'Paused', 'closed' => 'Closed'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-[13px]" :class="form.status === '{{ $value }}' ? 'border-accent bg-accent-soft' : 'border-line'">
                                <input type="radio" class="sr-only" value="{{ $value }}" x-model="form.status"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <p class="rounded-lg bg-subtle p-3 text-xs text-ink-soft ring-1 ring-line">Demo: changes are kept in the demo store, not a database. New branches start without order history.</p>
            </div>
            <footer class="flex justify-end gap-2 border-t border-line px-5 py-4">
                <button type="button" class="btn-secondary" @click="open = false">Cancel</button>
                <button class="btn-primary" :disabled="saving"><span x-text="editing ? 'Save changes' : 'Create branch'"></span></button>
            </footer>
        </form>
    </x-dashboard.slide-over>
</div>
@endsection

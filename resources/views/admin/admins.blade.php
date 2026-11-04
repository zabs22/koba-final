@extends('layouts.admin')

@section('title', 'Branch admins')

@section('content')
<div x-data="{ rows: @js($rows),
        async update(row, change) {
            const data = await this.$action(@js(route('admin.admins.update')), { email: row.email, ...change });
            if (data) this.rows = data.rows;
        } }">
    <x-dashboard.page-header eyebrow="Operations" title="Branch admins"
        description="Who runs each café's dashboard. Reassigning or deactivating applies at once — try it with the Atlas demo login." />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="row in rows" :key="row.email">
            <article class="card flex flex-col p-5" :class="!row.active && 'opacity-70'">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 place-items-center rounded-full bg-forest-900 font-display text-sm font-semibold text-sand-300" x-text="row.initials"></span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 font-display text-[15px] font-semibold">
                            <span x-text="row.name"></span>
                            <span x-show="row.login" class="rounded-full bg-sand-100 px-2 py-0.5 text-[10.5px] font-semibold text-brown-700 dark:bg-sand-400/15 dark:text-sand-300">Demo login</span>
                        </p>
                        <p class="truncate text-xs text-ink-faint" x-text="row.email"></p>
                    </div>
                    <span x-html="$badge(row.active ? 'active' : 'inactive')"></span>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-3 text-[13px]">
                    <div><dt class="text-xs text-ink-faint">Branch</dt><dd class="mt-0.5 font-medium" x-text="row.branch_name"></dd></div>
                    <div><dt class="text-xs text-ink-faint">Last seen</dt><dd class="mt-0.5 flex items-center gap-1.5"><span x-show="row.last_seen === 'Online'" class="live-dot"></span><span x-text="row.last_seen"></span></dd></div>
                </dl>

                <div class="mt-5 flex items-center gap-2 border-t border-line pt-4">
                    <label class="sr-only" :for="'branch-' + row.email">Assign branch</label>
                    <select :id="'branch-' + row.email" class="field h-9 flex-1 py-0 text-[13px]" :value="row.branch" @change="update(row, { branch: $event.target.value })">
                        @foreach ($branches as $b)<option value="{{ $b['key'] }}">{{ $b['short'] }}</option>@endforeach
                    </select>
                    <button type="button" class="btn-secondary btn-sm h-9" x-show="row.active"
                            @click="$dispatch('confirm', { title: 'Deactivate ' + row.name + '?', body: 'They keep their login but lose access to the ' + row.branch_name + ' dashboard until reactivated.', confirm: 'Deactivate', danger: true, then: () => update(row, { active: false }) })">Deactivate</button>
                    <button type="button" class="btn-primary btn-sm h-9" x-show="!row.active" @click="update(row, { active: true })">Activate</button>
                </div>
            </article>
        </template>
    </div>
</div>
@endsection

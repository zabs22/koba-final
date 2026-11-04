@extends('layouts.branch')

@section('title', 'Categories')

@section('content')
<div x-data="{
        rows: @js($rows), dirty: false, saving: false,
        move(i, step) {
            const j = i + step;
            if (j < 0 || j >= this.rows.length) return;
            [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]];
            this.dirty = true;
        },
        async save() {
            this.saving = true;
            const ok = await this.$action(@js(route('branch.categories.order')), { order: this.rows.map(r => r.key) }, 'PUT');
            this.saving = false;
            if (ok) this.dirty = false;
        },
     }">
    <x-dashboard.page-header eyebrow="Menu" title="Categories" description="The order customers see this café's menu in, and what each group sold in the last 30 days.">
        <x-slot:actions>
            <button type="button" class="btn-primary" :disabled="!dirty || saving" @click="save()"><x-dashboard.icon name="check" /> Save order</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        <x-dashboard.card class="xl:col-span-2" title="Menu order" subtitle="Use the arrows to reorder (or focus a row and press Alt + ↑ / ↓)" :padded="false">
            <ol class="divide-y divide-line">
                <template x-for="(c, i) in rows" :key="c.key">
                    <li class="flex items-center gap-4 px-5 py-3" tabindex="0"
                        @keydown.alt.arrow-up.prevent="move(i, -1); $nextTick(() => $el.previousElementSibling?.focus())"
                        @keydown.alt.arrow-down.prevent="move(i, 1); $nextTick(() => $el.nextElementSibling?.focus())">
                        <span class="text-ink-faint" x-html="$icon('grip-vertical', 'size-4')"></span>
                        <span class="w-5 font-display text-xs font-semibold text-ink-faint tabular-nums" x-text="i + 1"></span>
                        <span class="grid size-10 place-items-center rounded-xl bg-forest-900 text-sand-300" x-html="$icon(c.icon, 'size-4')"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium" x-text="c.name"></p>
                            <p class="text-xs text-ink-faint"><span x-text="c.available"></span> of <span x-text="c.products"></span> available today</p>
                        </div>
                        <div class="hidden w-40 sm:block">
                            <div class="h-1.5 overflow-hidden rounded-full bg-subtle ring-1 ring-line ring-inset"><div class="h-full rounded-full bg-forest-500" :style="`width: ${c.share}%`"></div></div>
                            <p class="mt-1 text-right text-xs tabular-nums text-ink-faint" x-text="$fmt(c.revenue, 'etb', true) + ' · ' + c.share + '%'"></p>
                        </div>
                        <div class="flex flex-col">
                            <button type="button" class="btn-icon size-7" :disabled="i === 0" @click="move(i, -1)" :aria-label="'Move ' + c.name + ' up'"><span x-html="$icon('arrow-up', 'size-3.5')"></span></button>
                            <button type="button" class="btn-icon size-7" :disabled="i === rows.length - 1" @click="move(i, 1)" :aria-label="'Move ' + c.name + ' down'"><span x-html="$icon('arrow-down', 'size-3.5')"></span></button>
                        </div>
                    </li>
                </template>
            </ol>
        </x-dashboard.card>

        <x-dashboard.card title="Sales by category" subtitle="Last 30 days">
            <x-dashboard.chart type="donut" format="etb" :height="320" :href="route('branch.products').'?category={key}'"
                :data="['labels' => array_column($rows, 'name'), 'keys' => array_column($rows, 'key'), 'series' => array_column($rows, 'revenue')]" />
        </x-dashboard.card>
    </div>
</div>
@endsection

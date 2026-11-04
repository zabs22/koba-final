{{-- Shown while the dashboards run on generated data: nothing here is a real order or payment. --}}
<span {{ $attributes->merge(['class' => 'items-center gap-1.5 rounded-full border border-dashed border-brown-500/40 px-2.5 py-1 text-[11.5px] font-medium text-brown-600 dark:border-sand-400/40 dark:text-sand-300']) }}
      title="Demo data: orders, customers and payments are generated for this preview. No real transactions.">
    <x-dashboard.icon name="sparkles" class="size-3.5" /> Demo data
</span>

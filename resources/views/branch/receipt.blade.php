{{-- Printable receipt (80mm-friendly). Opens in a new tab from the order drawer. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order['id'] }} · KOBA</title>
    @vite(['resources/css/dashboard.css'])
    <style>@page { size: 80mm auto; margin: 6mm; }</style>
</head>
<body class="bg-canvas py-8 text-ink print:bg-white print:py-0">
    <main class="mx-auto w-full max-w-[340px] rounded-xl border border-line bg-white p-6 font-sans text-[13px] shadow-[var(--shadow-card)] print:max-w-none print:border-0 print:p-0 print:shadow-none">
        <div class="text-center">
            <x-site.logo class="mx-auto h-9 w-auto" />
            <p class="mt-3 font-display text-sm font-semibold">{{ $branch['name'] }}</p>
            <p class="text-xs text-ink-soft">{{ $branch['area'] }} · {{ $branch['phone'] }}</p>
        </div>

        <div class="my-4 border-t border-dashed border-ink/30"></div>

        <dl class="grid grid-cols-2 gap-y-1 text-xs">
            <dt class="text-ink-soft">Order</dt><dd class="text-right font-mono font-semibold">{{ $order['id'] }}</dd>
            <dt class="text-ink-soft">Placed</dt><dd class="text-right">{{ $order['placed'] }}</dd>
            <dt class="text-ink-soft">Pickup</dt><dd class="text-right">{{ $order['pickup_date'] }}, {{ $order['pickup_time'] }}</dd>
            <dt class="text-ink-soft">Customer</dt><dd class="text-right">{{ $order['customer'] }}</dd>
            <dt class="text-ink-soft">Phone</dt><dd class="text-right">{{ $order['customer_phone'] }}</dd>
        </dl>

        <div class="my-4 border-t border-dashed border-ink/30"></div>

        <table class="w-full text-xs">
            <tbody>
                @foreach ($order['lines'] as $line)
                    <tr class="align-top">
                        <td class="py-1 pr-2">{{ $line['qty'] }} × {{ $line['name'] }}</td>
                        <td class="py-1 text-right tabular-nums whitespace-nowrap">{{ number_format($line['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="my-4 border-t border-dashed border-ink/30"></div>

        <div class="flex items-baseline justify-between">
            <span class="font-display font-semibold">Total</span>
            <span class="font-display text-lg font-semibold tabular-nums">{{ \App\Services\Dashboard\OrderPresenter::etb($order['total']) }}</span>
        </div>
        <p class="mt-1 flex justify-between text-xs text-ink-soft"><span>{{ $order['payment_label'] }}</span><span class="font-mono">{{ $order['reference'] }}</span></p>
        <p class="mt-1 text-xs text-ink-soft">Status: {{ $order['status_label'] }}</p>

        <div class="my-4 border-t border-dashed border-ink/30"></div>
        <p class="text-center text-xs text-ink-soft">Thank you — made with care at KOBA.</p>
        <p class="mt-2 text-center text-[10px] tracking-wider text-ink-faint uppercase">Demo receipt · not a tax invoice</p>

        <div class="mt-6 flex justify-center gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-primary btn-sm">Print</button>
            <button type="button" onclick="window.close()" class="btn-secondary btn-sm">Close</button>
        </div>
    </main>
</body>
</html>

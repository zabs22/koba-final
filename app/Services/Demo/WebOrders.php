<?php

namespace App\Services\Demo;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Turns the website's cake order form into a demo order for the chosen café,
 * so it appears on that branch's Live Orders board and, for a signed-in
 * customer, on their dashboard. Replace with a real Order model later.
 */
class WebOrders
{
    /** Whole cakes are priced by size: demo price = slice price × slices. */
    private const CAKE_SLICES = ['small' => 6, 'medium' => 10, 'large' => 14, 'advise' => 10];

    public function __construct(private DemoDataService $data) {}

    /** @param  array  $input  validated CakeOrderRequest data */
    public function place(array $input, ?User $user): array
    {
        $branch = $this->data->branch($input['branch']);
        if (($branch['status'] ?? 'closed') !== 'open') {
            throw ValidationException::withMessages(['branch' => __('koba.order.branch_paused', ['branch' => $branch['name'] ?? $input['branch']])]);
        }

        $customer = $this->data->customerIndexFor($user?->email ?? 'guest:'.preg_replace('/\D/', '', $input['phone']));

        return $this->data->placeOrder($input['branch'], $customer, $this->lines($input['items']), extra: [
            'contact' => ['name' => $input['name'], 'phone' => $input['phone']],
            'notes' => $input['notes'] ?? null,
            'source' => 'web',
            'payment' => 'cash',
        ]);
    }

    /** Private link to the order's confirmation page (works for guests too). */
    public function confirmationUrl(array $order): string
    {
        return URL::signedRoute('order.confirmed', ['order' => $order['id']]);
    }

    /** Cart items → [product id, qty, label, unit price], matched to the menu by slug. */
    private function lines(array $items): array
    {
        $products = collect($this->data->products());
        $bySlug = $products->keyBy('slug');
        $cakes = [...array_keys((array) trans('koba.cakes')), 'custom'];
        $sizes = (array) trans('koba.order.sizes');

        return array_map(function (array $item) use ($products, $bySlug, $cakes, $sizes) {
            $size = $item['size'] ?? null;
            $cake = $size !== null || in_array($item['slug'], $cakes, true);
            $product = $bySlug->get($item['slug'])
                ?? $products->firstWhere('category', $cake ? 'cakes' : 'pastries');
            $label = $bySlug->has($item['slug']) ? $product['title'] : $item['name'];

            return [
                $product['id'],
                (int) $item['qty'],
                $label.($size ? ' · '.$sizes[$size] : ''),
                (float) ($size ? $product['price'] * self::CAKE_SLICES[$size] : $product['price']),
            ];
        }, $items);
    }
}

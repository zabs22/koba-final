<?php

namespace App\Services\Dashboard;

use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\PickupCode;
use Carbon\CarbonImmutable;

/**
 * Turns raw order arrays into what tables, drawers and JSON endpoints show.
 */
class OrderPresenter
{
    private const NEXT = ['pending' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed'];

    private ?array $products = null;

    private ?array $customers = null;

    private ?array $branches = null;

    public function __construct(private DemoDataService $data) {}

    public static function etb(float $amount, int $decimals = 2): string
    {
        return 'ETB '.number_format($amount, $decimals);
    }

    public static function next(string $status): ?string
    {
        return self::NEXT[$status] ?? null;
    }

    public function time(int $timestamp, string $format = 'H:i'): string
    {
        return CarbonImmutable::createFromTimestamp($timestamp, config('dashboard.timezone'))->format($format);
    }

    public function rows(array $orders): array
    {
        return array_map(fn ($o) => $this->row($o), $orders);
    }

    public function row(array $o): array
    {
        $this->products ??= $this->data->products();
        $this->customers ??= $this->data->customers();
        $this->branches ??= $this->data->branches();
        $customer = $this->customers[$o['customer']];
        // Website orders carry the name the customer typed.
        $name = $o['contact']['name'] ?? $customer['name'];

        return [
            'id' => $o['id'],
            'branch' => $o['branch'],
            'branch_name' => $this->branches[$o['branch']]['short'] ?? $o['branch'],
            'customer' => $name,
            'customer_id' => $customer['id'],
            'initials' => DemoCatalog::initials($name),
            'items' => implode(' · ', array_map(fn ($line) => ($line[2] ?? $this->products[$line[0]]['name']).($line[1] > 1 ? ' ×'.$line[1] : ''), $o['items'])),
            'items_count' => array_sum(array_column($o['items'], 1)),
            'total' => $o['total'],
            'payment' => $o['payment'],
            'payment_label' => DemoCatalog::PAYMENTS[$o['payment']]['label'],
            'payment_status' => $this->paymentStatus($o),
            'status' => $o['status'],
            'status_label' => DemoCatalog::STATUS_LABELS[$o['status']],
            'placed_at' => $o['placed_at'],
            'pickup_at' => $o['pickup_at'],
            'status_at' => $o['status_at'] ?? $o['pickup_at'],
            'pickup_time' => $this->time($o['pickup_at']),
            'pickup_date' => $this->time($o['pickup_at'], 'D j M'),
            'placed' => $this->time($o['placed_at'], 'j M, H:i'),
            'scheduled' => $o['scheduled'],
            'prep' => $o['prep'],
            'rating' => $o['rating'],
            'next' => self::next($o['status']),
            'pickup_code' => PickupCode::for($o),
            'source' => $o['source'] ?? null,
        ];
    }

    /** Everything the order drawer, receipt and customer tracker need. */
    public function detail(array $o): array
    {
        $row = $this->row($o);
        $customer = $this->customers[$o['customer']];
        $branch = $this->branches[$o['branch']] ?? [];
        $cakes = array_keys((array) trans('koba.cakes'));

        $lines = array_map(function ($line) use ($cakes) {
            $p = $this->products[$line[0]];
            $price = $line[3] ?? $p['price'];

            return [
                'id' => $p['id'],
                'name' => $line[2] ?? $p['name'],
                'title' => $line[2] ?? $p['title'],
                'category' => DemoCatalog::CATEGORIES[$p['category']]['name'],
                'qty' => $line[1],
                'price' => $price,
                'subtotal' => $price * $line[1],
                'image' => $p['image_url'],
                // Same item shape as the public site's cart (resources/js/site/cart.js).
                'cart' => ['slug' => $p['slug'], 'name' => $p['name'], 'title' => $p['title'], 'image' => $p['image_url'] ?? '', 'cake' => in_array($p['slug'], $cakes, true), 'qty' => $line[1]],
            ];
        }, $o['items']);

        return [
            ...$row,
            'lines' => $lines,
            'customer_phone' => $o['contact']['phone'] ?? $customer['phone'],
            'customer_email' => isset($o['contact']) ? null : $customer['email'],
            'notes' => $o['notes'] ?? null,
            'pickup_code_display' => PickupCode::display($row['pickup_code']),
            'branch_full' => $branch['name'] ?? $row['branch_name'],
            'branch_area' => $branch['area'] ?? null,
            'branch_phone' => $branch['phone'] ?? null,
            'maps' => $branch['maps'] ?? null,
            'reference' => $this->reference($o),
            'timeline' => $this->timeline($o),
            'eta' => $this->eta($o),
            'can_cancel' => in_array($o['status'], ['pending', 'preparing'], true),
        ];
    }

    /** Order details plus the QR code: the customer's pickup pass. */
    public function pass(array $o): array
    {
        return [...$this->detail($o), 'qr' => PickupCode::svg(PickupCode::payload($o))];
    }

    public function transaction(array $o): array
    {
        $row = $this->row($o);

        return [
            'id' => $this->reference($o),
            'order' => $o['id'],
            'customer' => $row['customer'],
            'initials' => $row['initials'],
            'branch' => $o['branch'],
            'branch_name' => $row['branch_name'],
            'method' => $o['payment'],
            'method_label' => $row['payment_label'],
            'amount' => $o['total'],
            'status' => $row['payment_status'],
            'time' => $this->time($o['placed_at'], 'j M, H:i'),
            'placed_at' => $o['placed_at'],
        ];
    }

    private function reference(array $o): string
    {
        return 'TX-'.strtoupper(substr(md5($o['id']), 0, 8));
    }

    private function paymentStatus(array $o): string
    {
        return match (true) {
            $o['status'] === 'cancelled' => $o['payment'] === 'cash' ? 'void' : 'refunded',
            $o['payment'] === 'cash' && $o['status'] !== 'completed' => 'due',
            default => 'paid',
        };
    }

    /** Estimated ready time (minutes from now) while the order is still with the kitchen. */
    private function eta(array $o): ?array
    {
        if (! in_array($o['status'], ['pending', 'preparing'], true)) {
            return null;
        }
        $ready = $o['pickup_at'] - 180;

        return ['ready_at' => $ready, 'ready_time' => $this->time($ready)];
    }

    private function timeline(array $o): array
    {
        $rank = array_flip(['pending', 'preparing', 'ready', 'completed']);
        $current = $o['status'] === 'cancelled' ? -1 : $rank[$o['status']];
        $times = [
            'pending' => $o['placed_at'],
            'preparing' => $current >= 1 ? max($o['placed_at'] + 240, $o['pickup_at'] - $o['prep'] * 60 - 240) : null,
            'ready' => $current >= 2 ? $o['pickup_at'] - 180 : null,
            'completed' => $current >= 3 ? ($o['status_at'] ?? $o['pickup_at']) : null,
        ];
        // The step the order is on now happened when its status changed.
        if ($current > 0) {
            $times[$o['status']] = min($times[$o['status']] ?? PHP_INT_MAX, $o['status_at'] ?? PHP_INT_MAX);
        }

        $labels = ['pending' => 'Placed', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Picked up'];

        return array_map(fn ($status) => [
            'status' => $status,
            'label' => $labels[$status],
            'done' => $current >= $rank[$status],
            'current' => $current === $rank[$status],
            'time' => $times[$status] ? $this->time($times[$status]) : null,
        ], array_keys($labels));
    }
}

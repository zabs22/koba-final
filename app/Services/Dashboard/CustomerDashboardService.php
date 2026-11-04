<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use Carbon\CarbonImmutable;

/**
 * A customer's own orders, pickups, favourites and notifications.
 * Customers are matched to a demo customer profile by email.
 */
class CustomerDashboardService
{
    private const ACTIVE = ['pending', 'preparing', 'ready'];

    public function __construct(
        private DemoDataService $data,
        private DemoState $state,
        private OrderPresenter $present,
    ) {}

    public function profile(User $user): int
    {
        return $this->data->customerIndexFor($user->email);
    }

    /**
     * So the tracker always has something to follow: while the customer's home
     * café is open (07:00–21:00), a customer with nothing in the kitchen gets a
     * new demo order, a couple of minutes after the last one was picked up.
     */
    public function ensureDemoOrder(User $user): void
    {
        $id = $this->profile($user);
        $now = $this->data->now();
        $key = "autostart:$id";
        $home = $this->data->customers()[$id]['home'] ?? array_key_first($this->data->branches());
        $branch = $this->data->branch($home);

        if (! $branch || $branch['status'] !== 'open' || $now->hour < 7 || $now->hour >= 21) {
            return;
        }

        // The dashboard and the tracker's poll can arrive together: one order per minute at most.
        if ($now->timestamp - (int) $this->state->get($key, 0) < 60) {
            return;
        }

        $today = array_filter($this->orders($user), fn ($o) => $o['pickup_at'] >= $now->startOfDay()->timestamp);
        foreach ($today as $o) {
            $justCollected = $o['status'] === 'completed' && $o['status_at'] > $now->timestamp - 120;
            if (in_array($o['status'], self::ACTIVE, true) || $justCollected) {
                return;
            }
        }

        $this->state->put($key, $now->timestamp, 2);
        $this->data->placeOrder($home, $id, [[1, 1], [15, 1]]);
    }

    public function overview(User $user): array
    {
        $orders = $this->orders($user);
        $valid = array_values(array_filter($orders, fn ($o) => $o['status'] !== 'cancelled'));
        $spent = array_sum(array_column($valid, 'total'));
        $branches = $this->data->branches();
        $byBranch = array_count_values(array_column($valid, 'branch'));
        arsort($byBranch);
        $favouriteBranch = array_key_first($byBranch);

        return [
            'stats' => [
                'orders' => count($valid),
                'spent' => $spent,
                'cafe' => $favouriteBranch ? $branches[$favouriteBranch]['short'] : '—',
                'cafe_visits' => $favouriteBranch ? $byBranch[$favouriteBranch] : 0,
                'points' => (int) floor($spent / 10),
                'next_reward' => 2500 - ((int) floor($spent / 10) % 2500),
            ],
            'active' => $this->active($user),
            'next' => $this->nextPickup($orders),
            'charts' => [
                'spending' => $this->monthlySpending($valid),
                'categories' => $this->favouriteCategories($valid),
            ],
            'recent' => array_map(fn ($o) => $this->present->detail($o), array_slice(array_reverse($orders), 0, 5)),
            'favorites' => $this->favorites($user),
        ];
    }

    /** The order the live tracker follows: the soonest pickup still in progress, else the last one picked up today. */
    public function active(User $user): ?array
    {
        $today = array_filter($this->orders($user), fn ($o) => $o['pickup_at'] >= $this->data->now()->startOfDay()->timestamp);
        $active = array_values(array_filter($today, fn ($o) => in_array($o['status'], self::ACTIVE, true)));

        if ($active) {
            usort($active, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);

            // With the QR: the dashboard is where customers open their pickup pass.
            return $this->present->pass($active[0]);
        }

        $recent = array_values(array_filter($today, fn ($o) => $o['status'] === 'completed' && $o['status_at'] > $this->data->now()->timestamp - 1800));

        return $recent ? $this->present->detail(end($recent)) : null;
    }

    public function ordersTable(User $user): array
    {
        return $this->present->rows(array_reverse($this->orders($user)));
    }

    public function order(User $user, string $id): ?array
    {
        $order = collect($this->orders($user))->firstWhere('id', $id);

        return $order ? $this->present->detail($order) : null;
    }

    public function pickups(User $user): array
    {
        $orders = $this->orders($user);
        $upcoming = array_values(array_filter($orders, fn ($o) => in_array($o['status'], self::ACTIVE, true)));
        $past = array_slice(array_reverse(array_filter($orders, fn ($o) => in_array($o['status'], ['completed', 'cancelled'], true))), 0, 24);

        return [
            'upcoming' => array_map(fn ($o) => $this->present->detail($o), $upcoming),
            'past' => $this->present->rows($past),
        ];
    }

    public function favorites(User $user): array
    {
        $id = $this->profile($user);
        $saved = $this->state->get("favorites:$id");

        if ($saved === null) {
            // Until they choose, favourites are what they order most.
            $valid = array_filter($this->orders($user), fn ($o) => $o['status'] !== 'cancelled');
            $branchCounts = array_count_values(array_column($valid, 'branch'));
            arsort($branchCounts);
            $productCounts = [];
            foreach ($valid as $o) {
                foreach ($o['items'] as [$pid, $qty]) {
                    $productCounts[$pid] = ($productCounts[$pid] ?? 0) + $qty;
                }
            }
            arsort($productCounts);
            $saved = ['branches' => array_slice(array_keys($branchCounts), 0, 2), 'products' => array_slice(array_keys($productCounts), 0, 6)];
        }

        $branches = $this->data->branches();
        $products = $this->data->products();
        $cakes = array_keys((array) trans('koba.cakes'));

        return [
            'branch_keys' => $saved['branches'],
            'product_ids' => $saved['products'],
            'branches' => array_values(array_map(fn ($b) => [
                'key' => $b['key'], 'name' => $b['name'], 'short' => $b['short'], 'area' => $b['area'], 'phone' => $b['phone'], 'maps' => $b['maps'], 'status' => $b['status'],
                'image' => DemoCatalog::image($b['image']), 'favorite' => in_array($b['key'], $saved['branches'], true),
                'hours' => sprintf('%02d:00–%02d:00', ...$b['hours']['weekday']),
            ], $branches)),
            'products' => array_values(array_map(fn ($p) => [
                'id' => $p['id'], 'name' => $p['name'], 'title' => $p['title'], 'price' => $p['price'], 'image' => $p['image_url'],
                'category' => DemoCatalog::CATEGORIES[$p['category']]['name'], 'icon' => DemoCatalog::CATEGORIES[$p['category']]['icon'],
                'favorite' => in_array($p['id'], $saved['products'], true),
                'cart' => ['slug' => $p['slug'], 'name' => $p['name'], 'title' => $p['title'], 'image' => $p['image_url'] ?? '', 'cake' => in_array($p['slug'], $cakes, true), 'qty' => 1],
            ], $products)),
        ];
    }

    public function toggleFavorite(User $user, string $type, string $value): array
    {
        $current = $this->favorites($user);
        $branches = $current['branch_keys'];
        $products = $current['product_ids'];

        if ($type === 'branch') {
            $branches = in_array($value, $branches, true) ? array_values(array_diff($branches, [$value])) : [...$branches, $value];
        } else {
            $products = in_array((int) $value, $products, true) ? array_values(array_diff($products, [(int) $value])) : [...$products, (int) $value];
        }

        $this->state->put('favorites:'.$this->profile($user), ['branches' => $branches, 'products' => $products]);

        return $this->favorites($user);
    }

    public function notifications(User $user): array
    {
        $readUntil = (int) $this->state->get('notifications-read:'.$this->profile($user), 0);
        $items = [];

        foreach (array_slice(array_reverse($this->orders($user)), 0, 12) as $o) {
            $row = $this->present->row($o);
            [$icon, $tone, $title] = match ($o['status']) {
                'ready' => ['package-check', 'success', "Your order is ready at {$row['branch_name']}"],
                'completed' => ['circle-check', 'muted', 'Picked up — enjoy!'],
                'cancelled' => ['circle-x', 'danger', 'Order cancelled'],
                'preparing' => ['chef-hat', 'sand', 'The kitchen has started your order'],
                default => ['receipt-text', 'sand', 'Order received'],
            };
            $items[] = ['icon' => $icon, 'tone' => $tone, 'title' => $title, 'body' => $o['id'].' · '.$row['items'], 'at' => $o['status_at'] ?? $o['pickup_at'], 'href' => route('customer.orders', ['order' => $o['id']])];
        }

        $items[] = ['icon' => 'gift', 'tone' => 'sand', 'title' => 'Double points on weekend breakfast', 'body' => 'Saturday and Sunday, 06:00–11:00 at every KOBA café.', 'at' => $this->data->now()->startOfWeek()->addHours(9)->timestamp, 'href' => route('customer.favorites')];
        usort($items, fn ($a, $b) => $b['at'] <=> $a['at']);

        return array_map(fn ($n) => [
            ...$n,
            'unread' => $n['at'] > $readUntil,
            'when' => CarbonImmutable::createFromTimestamp($n['at'], config('dashboard.timezone'))->diffForHumans(),
        ], $items);
    }

    public function markNotificationsRead(User $user): void
    {
        $this->state->put('notifications-read:'.$this->profile($user), $this->data->now()->timestamp);
    }

    private function orders(User $user): array
    {
        return $this->data->customerOrders($this->profile($user));
    }

    private function nextPickup(array $orders): ?array
    {
        $upcoming = array_values(array_filter($orders, fn ($o) => in_array($o['status'], self::ACTIVE, true)));
        usort($upcoming, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);

        return $upcoming ? $this->present->detail($upcoming[0]) : null;
    }

    private function monthlySpending(array $orders): array
    {
        $months = [];
        $start = $this->data->historyStart()->startOfMonth();
        for ($m = $start; $m <= $this->data->now(); $m = $m->addMonth()) {
            $months[$m->format('Y-m')] = ['label' => $m->format('M'), 'value' => 0.0];
        }
        foreach ($orders as $o) {
            $key = $this->present->time($o['pickup_at'], 'Y-m');
            if (isset($months[$key])) {
                $months[$key]['value'] += $o['total'];
            }
        }

        return [
            'categories' => array_column($months, 'label'),
            'series' => [['name' => 'Spent', 'data' => array_map(fn ($m) => round($m['value'], 2), array_values($months))]],
        ];
    }

    private function favouriteCategories(array $orders): array
    {
        $products = $this->data->products();
        $counts = [];
        foreach ($orders as $o) {
            foreach ($o['items'] as [$id, $qty]) {
                $cat = $products[$id]['category'];
                $counts[$cat] = ($counts[$cat] ?? 0) + $qty;
            }
        }
        arsort($counts);

        return ['labels' => array_map(fn ($k) => DemoCatalog::CATEGORIES[$k]['name'], array_keys($counts)), 'series' => array_values($counts)];
    }
}

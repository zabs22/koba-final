<?php

namespace App\Services\Dashboard;

use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\Metrics;

/**
 * One branch's figures. Every method takes the branch key from the signed-in
 * admin (never from the request), so a branch only ever sees its own orders.
 */
class BranchDashboardService
{
    private const ACTIVE = ['pending', 'preparing', 'ready'];

    public function __construct(
        private DemoDataService $data,
        private DemoState $state,
        private OrderPresenter $present,
    ) {}

    public function overview(string $branch, DateRange $range): array
    {
        $previous = $range->previous();
        $orders = $this->data->ordersIn($range, $branch);
        $prev = $this->data->ordersIn($previous, $branch);
        $today = $this->data->todayOrders($branch);
        $buckets = $range->buckets();
        $products = $this->data->products($branch);
        $settings = $this->settings($branch);
        $count = fn (array $set, string $status) => count(array_filter($set, fn ($o) => $o['status'] === $status));
        $statusSpark = fn (string $status) => Metrics::series($today, DateRange::make('today')->buckets(), fn ($g) => $count($g, $status));

        $sold = Metrics::products($orders);
        $top = array_slice($sold, 0, 8, true);

        return [
            'range' => $range->toArray(),
            'updated' => $this->data->now()->format('H:i:s'),
            'kpis' => [
                'orders' => Metrics::kpi('Orders', count($orders), count($prev), 'number', 'receipt-text', Metrics::series($orders, $buckets)),
                'pending' => Metrics::kpi('New · now', $count($today, 'pending'), null, 'number', 'bell-ring', $statusSpark('pending')),
                'preparing' => Metrics::kpi('Preparing · now', $count($today, 'preparing'), null, 'number', 'chef-hat', $statusSpark('preparing')),
                'ready' => Metrics::kpi('Ready for pickup', $count($today, 'ready'), null, 'number', 'package-check', $statusSpark('ready')),
                'completed' => Metrics::kpi('Picked up', $count($orders, 'completed'), $count($prev, 'completed'), 'number', 'circle-check', Metrics::series($orders, $buckets, fn ($g) => $count($g, 'completed'))),
                'revenue' => Metrics::kpi('Revenue', Metrics::revenue($orders), Metrics::revenue($prev), 'etb', 'banknote', Metrics::revenueSeries($orders, $buckets)),
                'prep' => Metrics::kpi('Avg. prep time', Metrics::avgPrep($orders), Metrics::avgPrep($prev), 'minutes', 'timer', Metrics::series($orders, $buckets, fn ($g) => Metrics::avgPrep($g)), true),
                'cancel' => Metrics::kpi('Cancellation rate', Metrics::cancellationRate($orders), Metrics::cancellationRate($prev), 'percent', 'ban', Metrics::series($orders, $buckets, fn ($g) => Metrics::cancellationRate($g)), true),
            ],
            'charts' => [
                'sales' => [
                    'categories' => array_column($buckets, 'label'),
                    'series' => [
                        ['name' => 'This period', 'data' => Metrics::revenueSeries($orders, $buckets)],
                        ['name' => 'Previous period', 'data' => Metrics::revenueSeries($prev, $previous->buckets())],
                    ],
                ],
                'hours' => $this->byHour($orders, $prev, $range),
                'products' => [
                    'categories' => array_map(fn ($id) => $products[$id]['name'], array_keys($top)),
                    'series' => [['name' => 'Units sold', 'data' => array_values(array_column($top, 'units'))]],
                ],
                'categories' => (function () use ($orders, $products) {
                    $revenue = array_filter(Metrics::categories($orders, $products));

                    return ['labels' => array_map(fn ($k) => DemoCatalog::CATEGORIES[$k]['name'], array_keys($revenue)), 'keys' => array_keys($revenue), 'series' => array_values($revenue)];
                })(),
                'prep' => [
                    'categories' => array_column($buckets, 'label'),
                    'target' => (int) $settings['prep_target'],
                    'series' => [['name' => 'Avg. prep (min)', 'data' => Metrics::series($orders, $buckets, fn ($g) => Metrics::avgPrep($g))]],
                ],
                'pickups' => $this->pickupTimeline($today),
            ],
            'top' => array_values(array_map(fn ($id) => [
                'id' => $id,
                'name' => $products[$id]['name'],
                'title' => $products[$id]['title'],
                'image' => $products[$id]['image_url'],
                'category' => DemoCatalog::CATEGORIES[$products[$id]['category']]['name'],
                'units' => $sold[$id]['units'],
                'revenue' => $sold[$id]['units'] * $products[$id]['price'],
            ], array_slice(array_keys($top), 0, 5))),
            'recent' => $this->present->rows(array_slice(array_reverse(array_filter($orders, fn ($o) => $o['placed_at'] <= $this->data->now()->timestamp)), 0, 8)),
            'stock' => array_values(array_filter(array_map(fn ($p) => [
                'id' => $p['id'], 'name' => $p['name'], 'image' => $p['image_url'], 'stock' => $p['stock'], 'left' => $p['left'], 'available' => $p['available'],
            ], $products), fn ($p) => $p['stock'] !== 'ok' || ! $p['available'])),
            'schedule' => $this->upcoming($today, 8),
        ];
    }

    /** Live Orders board: active orders by column plus the last few picked up. */
    public function feed(string $branch): array
    {
        $today = $this->data->todayOrders($branch);
        $now = $this->data->now()->timestamp;
        $horizon = $now + 90 * 60;
        $columns = ['pending' => [], 'preparing' => [], 'ready' => [], 'completed' => []];
        $later = 0;

        foreach ($today as $o) {
            if ($o['status'] === 'cancelled') {
                continue;
            }
            if ($o['status'] === 'pending' && $o['pickup_at'] > $horizon) {
                $later++;

                continue;
            }
            $columns[$o['status']][] = $this->present->row($o);
        }

        usort($columns['completed'], fn ($a, $b) => $b['status_at'] <=> $a['status_at']);
        $columns['completed'] = array_slice($columns['completed'], 0, 6);
        foreach (['pending', 'preparing', 'ready'] as $status) {
            usort($columns[$status], fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);
        }

        return [
            'columns' => $columns,
            'later' => $later,
            'now' => $now,
            'status' => $this->data->branch($branch)['status'] ?? 'open',
            'counts' => array_map('count', $columns),
        ];
    }

    public function ordersTable(string $branch, DateRange $range): array
    {
        $now = $this->data->now()->timestamp;

        return $this->present->rows(array_reverse(array_filter(
            $this->data->ordersIn($range, $branch),
            fn ($o) => $o['placed_at'] <= $now,
        )));
    }

    public function productsTable(string $branch, DateRange $range): array
    {
        $sold = Metrics::products($this->data->ordersIn($range, $branch));

        return array_values(array_map(fn ($p) => [
            'id' => $p['id'],
            'sku' => $p['sku'],
            'name' => $p['name'],
            'title' => $p['title'],
            'image' => $p['image_url'],
            'category' => $p['category'],
            'category_name' => DemoCatalog::CATEGORIES[$p['category']]['name'],
            'price' => $p['price'],
            'available' => $p['available'],
            'stock' => $p['stock'],
            'left' => $p['left'],
            'units' => $sold[$p['id']]['units'] ?? 0,
        ], $this->data->products($branch)));
    }

    public function categories(string $branch, DateRange $range): array
    {
        $products = $this->data->products($branch);
        $revenue = Metrics::categories($this->data->ordersIn($range, $branch), $products);
        $total = array_sum($revenue) ?: 1;

        return array_values(array_map(function ($c) use ($products, $revenue, $total) {
            $mine = array_filter($products, fn ($p) => $p['category'] === $c['key']);

            return [
                ...$c,
                'products' => count($mine),
                'available' => count(array_filter($mine, fn ($p) => $p['available'])),
                'revenue' => round($revenue[$c['key']] ?? 0, 2),
                'share' => round(($revenue[$c['key']] ?? 0) / $total * 100, 1),
            ];
        }, $this->data->categories($branch)));
    }

    /** Today's pickups grouped by slot (slot length from branch settings). */
    public function pickupSlots(string $branch): array
    {
        $slot = (int) $this->settings($branch)['slot_minutes'] * 60;
        $now = $this->data->now()->timestamp;
        $slots = [];

        foreach ($this->data->todayOrders($branch) as $o) {
            if ($o['status'] === 'cancelled') {
                continue;
            }
            $start = intdiv($o['pickup_at'], $slot) * $slot;
            $slots[$start] ??= ['start' => $start, 'label' => $this->present->time($start).'–'.$this->present->time($start + $slot), 'past' => $start + $slot < $now, 'current' => $start <= $now && $now < $start + $slot, 'orders' => [], 'total' => 0];
            $slots[$start]['orders'][] = $this->present->row($o);
            $slots[$start]['total'] += $o['total'];
        }
        ksort($slots);

        return array_values($slots);
    }

    public function customersTable(string $branch): array
    {
        $rows = [];
        foreach ($this->data->allOrders($branch) as $o) {
            if ($o['status'] === 'cancelled') {
                continue;
            }
            $row = &$rows[$o['customer']];
            $row['orders'] = ($row['orders'] ?? 0) + 1;
            $row['spent'] = ($row['spent'] ?? 0) + $o['total'];
            $row['last'] = $o['pickup_at'];
            unset($row);
        }
        $customers = $this->data->customers();

        return array_values(array_map(fn ($id, $r) => [
            'id' => $id,
            'name' => $customers[$id]['name'],
            'initials' => DemoCatalog::initials($customers[$id]['name']),
            'phone' => $customers[$id]['phone'],
            'email' => $customers[$id]['email'],
            'orders' => $r['orders'],
            'spent' => round($r['spent'], 2),
            'last' => $this->present->time($r['last'], 'j M Y'),
            'last_at' => $r['last'],
            'segment' => $r['orders'] >= 12 ? 'VIP' : ($r['orders'] >= 4 ? 'Regular' : 'New'),
        ], array_keys($rows), $rows));
    }

    public function payments(string $branch, DateRange $range): array
    {
        $orders = $this->data->ordersIn($range, $branch);
        $buckets = $range->buckets();
        $split = Metrics::paymentSplit($orders);

        return [
            'range' => $range->toArray(),
            'kpis' => [
                'gross' => Metrics::kpi('Collected', Metrics::revenue($orders), null, 'etb', 'banknote', Metrics::revenueSeries($orders, $buckets)),
                'digital' => Metrics::kpi('Digital', $split['telebirr'] + $split['cbe_birr'] + $split['chapa'], null, 'etb', 'smartphone'),
                'cash' => Metrics::kpi('Cash on pickup', $split['cash'], null, 'etb', 'wallet'),
                'due' => Metrics::kpi('Cash still due', array_sum(array_column(array_filter($orders, fn ($o) => $o['payment'] === 'cash' && in_array($o['status'], self::ACTIVE, true)), 'total')), null, 'etb', 'hourglass'),
            ],
            'charts' => [
                'methods' => ['labels' => array_column(DemoCatalog::PAYMENTS, 'label'), 'series' => array_values($split)],
                'volume' => ['categories' => array_column($buckets, 'label'), 'series' => [['name' => 'Collected', 'data' => Metrics::revenueSeries($orders, $buckets)]]],
            ],
            'rows' => array_map(fn ($o) => $this->present->transaction($o), array_reverse(array_filter($orders, fn ($o) => $o['placed_at'] <= $this->data->now()->timestamp))),
        ];
    }

    public function settings(string $branch): array
    {
        $b = $this->data->branch($branch);

        return array_merge([
            'opens' => sprintf('%02d:00', $b['hours']['weekday'][0]),
            'closes' => sprintf('%02d:00', $b['hours']['weekday'][1]),
            'weekend_opens' => sprintf('%02d:00', $b['hours']['weekend'][0]),
            'weekend_closes' => sprintf('%02d:00', $b['hours']['weekend'][1]),
            'prep_target' => 15,
            'slot_minutes' => 15,
            'max_per_slot' => 12,
            'auto_accept' => false,
            'phone' => $b['phone'],
        ], (array) $this->state->get("branch-settings:$branch", []));
    }

    public function notifications(string $branch): array
    {
        $today = $this->data->todayOrders($branch);
        $now = $this->data->now()->timestamp;
        $items = [];

        foreach (array_reverse($today) as $o) {
            if ($o['status'] === 'pending' && $o['placed_at'] > $now - 3600) {
                $items[] = ['icon' => 'bell-ring', 'tone' => 'sand', 'title' => 'New order '.$o['id'], 'body' => $this->present->row($o)['customer'].' · pickup '.$this->present->time($o['pickup_at']), 'at' => $o['placed_at'], 'href' => route('branch.live-orders')];
            }
            if (in_array($o['status'], ['pending', 'preparing'], true) && $o['pickup_at'] < $now) {
                $items[] = ['icon' => 'alarm-clock', 'tone' => 'danger', 'title' => $o['id'].' is late', 'body' => 'Pickup was '.$this->present->time($o['pickup_at']), 'at' => $o['pickup_at'], 'href' => route('branch.live-orders')];
            }
        }
        foreach ($this->data->products($branch) as $p) {
            if ($p['stock'] !== 'ok') {
                $items[] = ['icon' => 'package-x', 'tone' => 'warning', 'title' => $p['name'].($p['stock'] === 'out' ? ' sold out' : ' running low'), 'body' => $p['stock'] === 'out' ? 'Hidden from the menu until restocked.' : "{$p['left']} left today.", 'at' => $this->data->now()->startOfDay()->addHours(7)->timestamp, 'href' => route('branch.products')];
            }
        }

        usort($items, fn ($a, $b) => $b['at'] <=> $a['at']);

        return array_map(fn ($n) => [...$n, 'when' => $this->present->time($n['at'])], array_slice($items, 0, 12));
    }

    private function byHour(array $orders, array $prev, DateRange $range): array
    {
        $tz = config('dashboard.timezone');
        $perHour = function (array $set, int $days) use ($tz) {
            $counts = array_fill(6, 17, 0);
            foreach ($set as $o) {
                $h = (int) (new \DateTimeImmutable('@'.$o['pickup_at']))->setTimezone(new \DateTimeZone($tz))->format('G');
                if (isset($counts[$h])) {
                    $counts[$h]++;
                }
            }

            return array_values(array_map(fn ($c) => round($c / max(1, $days), 1), $counts));
        };

        return [
            'categories' => array_map(fn ($h) => sprintf('%02d:00', $h), range(6, 22)),
            'series' => [
                ['name' => $range->days() > 1 ? 'Avg. per day' : 'Today', 'data' => $perHour($orders, $range->days())],
                ['name' => 'Previous period', 'data' => $perHour($prev, max(1, $range->previous()->days()))],
            ],
        ];
    }

    /** Upcoming pickups for the next three hours, as bars from kitchen start to pickup. */
    private function pickupTimeline(array $today): array
    {
        $now = $this->data->now()->timestamp;
        $rows = array_filter($today, fn ($o) => in_array($o['status'], self::ACTIVE, true) && $o['pickup_at'] >= $now - 1800 && $o['pickup_at'] <= $now + 3 * 3600);
        usort($rows, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);

        return [
            'now' => $now * 1000,
            'series' => [[
                'name' => 'Pickups',
                'data' => array_map(fn ($o) => [
                    'x' => $this->present->row($o)['customer'],
                    'y' => [max($o['placed_at'], $o['pickup_at'] - $o['prep'] * 60 - 240) * 1000, $o['pickup_at'] * 1000],
                    'status' => $o['status'],
                    'order' => $o['id'],
                ], array_slice($rows, 0, 12)),
            ]],
        ];
    }

    private function upcoming(array $today, int $limit): array
    {
        $rows = array_filter($today, fn ($o) => in_array($o['status'], self::ACTIVE, true));
        usort($rows, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);

        return $this->present->rows(array_slice($rows, 0, $limit));
    }
}

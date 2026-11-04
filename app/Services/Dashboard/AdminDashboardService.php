<?php

namespace App\Services\Dashboard;

use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Services\Demo\DemoState;
use App\Support\Dashboard\DateRange;
use App\Support\Dashboard\Metrics;
use Carbon\CarbonImmutable;

/**
 * Platform-wide figures for the super admin.
 */
class AdminDashboardService
{
    public function __construct(
        private DemoDataService $data,
        private DemoState $state,
        private OrderPresenter $present,
    ) {}

    /** Everything on the overview page; also served as JSON when the filters change. */
    public function overview(DateRange $range, ?string $branch = null): array
    {
        $previous = $range->previous();
        $all = $this->data->ordersIn($range);
        $allPrevious = $this->data->ordersIn($previous);
        $orders = $branch ? $this->forBranch($all, $branch) : $all;
        $prev = $branch ? $this->forBranch($allPrevious, $branch) : $allPrevious;
        $buckets = $range->buckets();
        $ledger = $this->data->customerLedger();
        $branches = $this->data->branches();

        $firsts = array_column($ledger, 'first');
        $customersBy = fn (int $ts) => count(array_filter($firsts, fn ($f) => $f <= $ts));
        $newIn = fn (int $from, int $to) => count(array_filter($firsts, fn ($f) => $f >= $from && $f <= $to));
        $digital = fn ($set) => count(array_filter(Metrics::valid($set), fn ($o) => $o['payment'] !== 'cash'));

        return [
            'range' => $range->toArray(),
            'branch' => $branch,
            'updated' => $this->data->now()->format('H:i:s'),
            'kpis' => [
                'branches' => Metrics::kpi('Total branches', count($branches), null, 'number', 'store'),
                'active' => Metrics::kpi('Open now', count(array_filter($branches, fn ($b) => $b['status'] === 'open')), null, 'number', 'circle-dot'),
                'customers' => Metrics::kpi('Total customers', $customersBy($range->to->timestamp), $customersBy($previous->to->timestamp), 'number', 'users',
                    array_map(fn ($b) => $customersBy($b['to']), $buckets)),
                'orders' => Metrics::kpi('Total orders', count($orders), count($prev), 'number', 'receipt-text', Metrics::series($orders, $buckets)),
                'revenue' => Metrics::kpi('Platform revenue', Metrics::revenue($orders), Metrics::revenue($prev), 'etb', 'banknote', Metrics::revenueSeries($orders, $buckets)),
                'transactions' => Metrics::kpi('Digital payments', $digital($orders), $digital($prev), 'number', 'credit-card',
                    Metrics::series($orders, $buckets, $digital)),
                'aov' => Metrics::kpi('Avg. order value', Metrics::aov($orders), Metrics::aov($prev), 'etb', 'shopping-bag',
                    Metrics::series($orders, $buckets, fn ($g) => Metrics::aov($g))),
                'new_customers' => Metrics::kpi('New customers', $newIn($range->from->timestamp, $range->to->timestamp), $newIn($previous->from->timestamp, $previous->to->timestamp), 'number', 'user-plus',
                    array_map(fn ($b) => $newIn($b['from'], $b['to']), $buckets)),
            ],
            'charts' => [
                'revenue' => [
                    'categories' => array_column($buckets, 'label'),
                    'series' => [
                        ['name' => 'This period', 'data' => Metrics::revenueSeries($orders, $buckets)],
                        ['name' => 'Previous period', 'data' => Metrics::revenueSeries($prev, $previous->buckets())],
                    ],
                ],
                'byBranch' => [
                    'categories' => array_column($buckets, 'label'),
                    'series' => array_values(array_map(fn ($b) => [
                        'name' => $b['short'],
                        'key' => $b['key'],
                        'data' => Metrics::revenueSeries($this->forBranch($all, $b['key']), $buckets),
                    ], array_filter($branches, fn ($b) => ! $branch || $b['key'] === $branch))),
                ],
                'status' => $this->statusChart($orders),
                'customers' => $this->customerGrowth($orders, $buckets, $ledger),
                'heatmap' => ['series' => Metrics::heatmap($this->heatmapOrders($range, $branch), config('dashboard.timezone'))],
                'categories' => $this->categoryChart($orders),
                'payments' => [
                    'labels' => array_column(DemoCatalog::PAYMENTS, 'label'),
                    'keys' => array_keys(DemoCatalog::PAYMENTS),
                    'series' => array_values(Metrics::paymentSplit($orders)),
                ],
            ],
            'leaderboard' => $this->leaderboard($all, $allPrevious, $buckets),
            'registrations' => $this->registrations($ledger),
            'transactions' => array_map(fn ($o) => $this->present->transaction($o), $this->latest($orders, 7)),
            'branches' => $this->branchStatus(),
            'alerts' => $this->alerts(),
        ];
    }

    public function branchesTable(DateRange $range): array
    {
        $orders = $this->data->ordersIn($range);
        $today = $this->data->todayOrders();

        return array_values(array_map(function ($b) use ($orders, $today) {
            $mine = $this->forBranch($orders, $b['key']);
            $now = $this->forBranch($today, $b['key']);

            return [
                'key' => $b['key'],
                'name' => $b['name'],
                'short' => $b['short'],
                'area' => $b['area'],
                'phone' => $b['phone'],
                'manager' => $b['manager'],
                'status' => $b['status'],
                'hours' => sprintf('%02d:00–%02d:00', ...$b['hours']['weekday']),
                'orders' => count($mine),
                'revenue' => Metrics::revenue($mine),
                'prep' => Metrics::avgPrep($mine),
                'rating' => Metrics::rating($mine),
                'active' => count(array_filter($now, fn ($o) => in_array($o['status'], ['pending', 'preparing', 'ready'], true))),
            ];
        }, $this->data->branches()));
    }

    public function admins(): array
    {
        $inactive = (array) $this->state->get('admins-inactive', []);
        $accounts = collect(config('dashboard.accounts'))->filter(fn ($a) => $a['role'] === 'branch_admin');
        $branches = $this->data->branches();
        $rows = [];

        // The real demo login first, then the other branches' (display-only) managers.
        foreach ($accounts as $email => $account) {
            $rows[$email] = ['email' => $email, 'name' => $branches[$account['branch']]['manager'] ?? 'Branch Admin', 'branch' => $this->state->get("admin-branch:$email", $account['branch']), 'login' => true];
        }
        foreach ($branches as $b) {
            if ($b['manager'] && ! collect($rows)->contains('name', $b['manager'])) {
                $email = strtolower(str_replace(' ', '.', $b['manager'])).'@example.com';
                $rows[$email] = ['email' => $email, 'name' => $b['manager'], 'branch' => $this->state->get("admin-branch:$email", $b['key']), 'login' => false];
            }
        }

        return array_values(array_map(fn ($r) => [
            ...$r,
            'initials' => DemoCatalog::initials($r['name']),
            'branch_name' => $branches[$r['branch']]['short'] ?? '—',
            'active' => ! in_array($r['email'], $inactive, true),
            'last_seen' => $r['login'] ? 'Online' : (1 + crc32($r['email']) % 50).' min ago',
        ], $rows));
    }

    public function customersTable(): array
    {
        $ledger = $this->data->customerLedger();
        $branches = $this->data->branches();
        $now = $this->data->now()->timestamp;

        return array_values(array_map(function ($c) use ($ledger, $branches, $now) {
            $l = $ledger[$c['id']] ?? null;
            $favourite = $l ? array_search(max($l['branches']), $l['branches'], true) : $c['home'];

            return [
                'id' => $c['id'],
                'name' => $c['name'],
                'initials' => DemoCatalog::initials($c['name']),
                'email' => $c['email'],
                'phone' => $c['phone'],
                'orders' => $l['orders'] ?? 0,
                'spent' => round($l['spent'] ?? 0, 2),
                'last' => $l ? $this->present->time($l['last'], 'j M Y') : '—',
                'last_at' => $l['last'] ?? 0,
                'since' => $l ? $this->present->time($l['first'], 'M Y') : '—',
                'branch' => $favourite,
                'branch_name' => $branches[$favourite]['short'] ?? '—',
                'segment' => $this->segment($l, $now),
            ];
        }, $this->data->customers()));
    }

    public function productsTable(DateRange $range): array
    {
        $sold = Metrics::products($this->data->ordersIn($range));

        return array_values(array_map(fn ($p) => [
            'id' => $p['id'],
            'sku' => $p['sku'],
            'name' => $p['name'],
            'title' => $p['title'],
            'image' => $p['image_url'],
            'category' => $p['category'],
            'category_name' => DemoCatalog::CATEGORIES[$p['category']]['name'],
            'price' => $p['price'],
            'units' => $sold[$p['id']]['units'] ?? 0,
            'revenue' => ($sold[$p['id']]['units'] ?? 0) * $p['price'],
            'active' => $p['active'],
        ], $this->data->products()));
    }

    public function categoriesTable(DateRange $range): array
    {
        $revenue = Metrics::categories($this->data->ordersIn($range), $this->data->products());
        $total = array_sum($revenue) ?: 1;
        $counts = array_count_values(array_column($this->data->products(), 'category'));

        return array_values(array_map(fn ($c) => [
            ...$c,
            'products' => $counts[$c['key']] ?? 0,
            'revenue' => round($revenue[$c['key']] ?? 0, 2),
            'share' => round(($revenue[$c['key']] ?? 0) / $total * 100, 1),
        ], $this->data->categories()));
    }

    /** Payments page / Revenue page: method split, daily volume, commission per branch. */
    public function finance(DateRange $range): array
    {
        $orders = $this->data->ordersIn($range);
        $prev = $this->data->ordersIn($range->previous());
        $buckets = $range->buckets();
        $commission = (float) $this->settings()['commission'];
        $valid = Metrics::valid($orders);
        $refunded = array_filter($orders, fn ($o) => $o['status'] === 'cancelled' && $o['payment'] !== 'cash');

        return [
            'range' => $range->toArray(),
            'kpis' => [
                'gross' => Metrics::kpi('Gross volume', Metrics::revenue($orders), Metrics::revenue($prev), 'etb', 'banknote', Metrics::revenueSeries($orders, $buckets)),
                'commission' => Metrics::kpi("Platform commission ({$commission}%)", Metrics::revenue($orders) * $commission / 100, Metrics::revenue($prev) * $commission / 100, 'etb', 'percent',
                    array_map(fn ($v) => round($v * $commission / 100, 2), Metrics::revenueSeries($orders, $buckets))),
                'count' => Metrics::kpi('Transactions', count($valid), count(Metrics::valid($prev)), 'number', 'credit-card', Metrics::series($valid, $buckets)),
                'refunds' => Metrics::kpi('Refunded', array_sum(array_column($refunded, 'total')), null, 'etb', 'undo-2', null, true),
            ],
            'charts' => [
                'volume' => [
                    'categories' => array_column($buckets, 'label'),
                    'series' => array_map(fn ($key, $p) => [
                        'name' => $p['label'],
                        'data' => Metrics::revenueSeries(array_values(array_filter($orders, fn ($o) => $o['payment'] === $key)), $buckets),
                    ], array_keys(DemoCatalog::PAYMENTS), DemoCatalog::PAYMENTS),
                ],
                'methods' => [
                    'labels' => array_column(DemoCatalog::PAYMENTS, 'label'),
                    'series' => array_values(Metrics::paymentSplit($orders)),
                ],
            ],
            'payouts' => array_values(array_map(function ($b) use ($orders, $commission) {
                $gross = Metrics::revenue($this->forBranch($orders, $b['key']));

                return ['key' => $b['key'], 'branch' => $b['short'], 'gross' => $gross, 'commission' => round($gross * $commission / 100, 2), 'net' => round($gross * (1 - $commission / 100), 2), 'rate' => $commission];
            }, $this->data->branches())),
        ];
    }

    /** Reports: each branch this period vs the previous one. */
    public function comparison(DateRange $range): array
    {
        $orders = $this->data->ordersIn($range);
        $prev = $this->data->ordersIn($range->previous());
        $branches = $this->data->branches();

        return [
            'range' => $range->toArray(),
            'charts' => [
                'compare' => [
                    'categories' => array_values(array_column($branches, 'short')),
                    'series' => [
                        ['name' => 'This period', 'data' => array_values(array_map(fn ($b) => Metrics::revenue($this->forBranch($orders, $b['key'])), $branches))],
                        ['name' => 'Previous period', 'data' => array_values(array_map(fn ($b) => Metrics::revenue($this->forBranch($prev, $b['key'])), $branches))],
                    ],
                ],
                'orders' => [
                    'categories' => array_column($range->buckets(), 'label'),
                    'series' => array_values(array_map(fn ($b) => ['name' => $b['short'], 'data' => Metrics::series($this->forBranch($orders, $b['key']), $range->buckets())], $branches)),
                ],
            ],
            'rows' => array_values(array_map(function ($b) use ($orders, $prev) {
                $now = $this->forBranch($orders, $b['key']);
                $before = $this->forBranch($prev, $b['key']);

                return [
                    'branch' => $b['short'],
                    'orders' => count($now), 'orders_prev' => count($before),
                    'revenue' => Metrics::revenue($now), 'revenue_prev' => Metrics::revenue($before),
                    'change' => Metrics::change(Metrics::revenue($now), Metrics::revenue($before)),
                    'aov' => round(Metrics::aov($now), 2),
                    'prep' => Metrics::avgPrep($now),
                    'cancel' => Metrics::cancellationRate($now),
                ];
            }, $branches)),
        ];
    }

    public function settings(): array
    {
        return array_merge([
            'platform_name' => 'KOBA Pre-Order',
            'currency' => 'ETB',
            'commission' => 8,
            'opens' => '07:00',
            'closes' => '22:00',
            'prep_target' => 15,
            'slot_minutes' => 15,
            'support_email' => config('koba.email'),
        ], (array) $this->state->get('settings', []));
    }

    public function alerts(): array
    {
        $today = $this->data->todayOrders();
        $now = $this->data->now()->timestamp;
        $target = (int) $this->settings()['prep_target'];
        $alerts = [];

        foreach ($this->data->branches() as $b) {
            $mine = $this->forBranch($today, $b['key']);
            if ($b['status'] !== 'open') {
                $alerts[] = ['level' => 'warning', 'title' => "{$b['short']} is ".($b['status'] === 'paused' ? 'paused' : 'closed'), 'body' => 'Customers cannot place pre-orders for this branch.', 'branch' => $b['key']];
            }
            $late = count(array_filter($mine, fn ($o) => in_array($o['status'], ['pending', 'preparing'], true) && $o['pickup_at'] < $now));
            if ($late) {
                $alerts[] = ['level' => 'danger', 'title' => "$late late ".str('order')->plural($late)." at {$b['short']}", 'body' => 'Past their pickup time and not ready yet.', 'branch' => $b['key']];
            }
            $prep = Metrics::avgPrep($mine);
            if ($prep > $target + 1) {
                $alerts[] = ['level' => 'warning', 'title' => "{$b['short']} kitchen is slow", 'body' => "Average prep today is {$prep} min (target {$target} min).", 'branch' => $b['key']];
            }
            $low = count(array_filter($this->data->products($b['key']), fn ($p) => $p['stock'] !== 'ok'));
            if ($low) {
                $alerts[] = ['level' => 'info', 'title' => "$low items running low at {$b['short']}", 'body' => 'Check the branch stock list.', 'branch' => $b['key']];
            }
        }

        return $alerts;
    }

    private function forBranch(array $orders, string $branch): array
    {
        return array_values(array_filter($orders, fn ($o) => $o['branch'] === $branch));
    }

    private function latest(array $orders, int $limit): array
    {
        $now = $this->data->now()->timestamp;
        $placed = array_filter(Metrics::valid($orders), fn ($o) => $o['placed_at'] <= $now);
        usort($placed, fn ($a, $b) => $b['placed_at'] <=> $a['placed_at']);

        return array_slice($placed, 0, $limit);
    }

    private function statusChart(array $orders): array
    {
        $counts = Metrics::statusCounts($orders);

        return ['labels' => array_values(DemoCatalog::STATUS_LABELS), 'keys' => array_keys($counts), 'series' => array_values($counts)];
    }

    private function categoryChart(array $orders): array
    {
        $revenue = Metrics::categories($orders, $this->data->products());

        return [
            'categories' => array_map(fn ($k) => DemoCatalog::CATEGORIES[$k]['name'], array_keys($revenue)),
            'keys' => array_keys($revenue),
            'series' => [['name' => 'Revenue', 'data' => array_map(fn ($v) => round($v, 2), array_values($revenue))]],
        ];
    }

    private function customerGrowth(array $orders, array $buckets, array $ledger): array
    {
        $new = $returning = [];
        foreach ($buckets as $i => $bucket) {
            $ids = array_unique(array_column(array_filter($orders, fn ($o) => $o['pickup_at'] >= $bucket['from'] && $o['pickup_at'] <= $bucket['to']), 'customer'));
            $fresh = count(array_filter($ids, fn ($id) => ($ledger[$id]['first'] ?? 0) >= $bucket['from']));
            $new[$i] = $fresh;
            $returning[$i] = count($ids) - $fresh;
        }

        return [
            'categories' => array_column($buckets, 'label'),
            'series' => [['name' => 'Returning', 'data' => $returning], ['name' => 'New', 'data' => $new]],
        ];
    }

    /** The heatmap needs at least four weeks to show a pattern. */
    private function heatmapOrders(DateRange $range, ?string $branch): array
    {
        $from = $range->days() >= 14 ? $range->from : $range->to->subDays(27)->startOfDay();

        return $this->data->orders($from, $range->to, $branch);
    }

    private function leaderboard(array $orders, array $previous, array $buckets): array
    {
        $total = Metrics::revenue($orders) ?: 1;
        $rows = array_map(function ($b) use ($orders, $previous, $buckets, $total) {
            $mine = $this->forBranch($orders, $b['key']);
            $revenue = Metrics::revenue($mine);

            return [
                'key' => $b['key'],
                'name' => $b['short'],
                'area' => $b['area'],
                'status' => $b['status'],
                'orders' => count($mine),
                'revenue' => $revenue,
                'share' => round($revenue / $total * 100, 1),
                'change' => Metrics::change($revenue, Metrics::revenue($this->forBranch($previous, $b['key']))),
                'prep' => Metrics::avgPrep($mine),
                'rating' => Metrics::rating($mine),
                'spark' => Metrics::revenueSeries($mine, $buckets),
            ];
        }, array_values($this->data->branches()));

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_map(fn ($row, $i) => ['rank' => $i + 1, ...$row], $rows, array_keys($rows));
    }

    private function registrations(array $ledger): array
    {
        uasort($ledger, fn ($a, $b) => $b['first'] <=> $a['first']);
        $customers = $this->data->customers();
        $branches = $this->data->branches();

        return array_values(array_map(fn ($id) => [
            'name' => $customers[$id]['name'],
            'initials' => DemoCatalog::initials($customers[$id]['name']),
            'branch' => $branches[$customers[$id]['home']]['short'] ?? '',
            'when' => CarbonImmutable::createFromTimestamp($ledger[$id]['first'], config('dashboard.timezone'))->diffForHumans(),
            'orders' => $ledger[$id]['orders'],
        ], array_slice(array_keys($ledger), 0, 6)));
    }

    private function branchStatus(): array
    {
        $today = $this->data->todayOrders();

        return array_values(array_map(function ($b) use ($today) {
            $mine = $this->forBranch($today, $b['key']);

            return [
                'key' => $b['key'],
                'name' => $b['short'],
                'area' => $b['area'],
                'status' => $b['status'],
                'active' => count(array_filter($mine, fn ($o) => in_array($o['status'], ['pending', 'preparing', 'ready'], true))),
                'revenue' => Metrics::revenue($mine),
                'prep' => Metrics::avgPrep($mine),
            ];
        }, $this->data->branches()));
    }

    private function segment(?array $ledger, int $now): string
    {
        return match (true) {
            ! $ledger => 'Inactive',
            $ledger['orders'] >= 25 || $ledger['spent'] >= 12000 => 'VIP',
            $ledger['first'] >= $now - 30 * 86400 => 'New',
            $ledger['orders'] >= 4 => 'Regular',
            default => 'Occasional',
        };
    }
}

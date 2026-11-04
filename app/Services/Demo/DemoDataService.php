<?php

namespace App\Services\Demo;

use App\Support\Dashboard\DateRange;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The single source of dashboard data while there is no order database.
 *
 * Orders are generated deterministically per branch and day (same seed, same
 * orders), so history is stable between requests. Today's orders appear as
 * their placement time passes and move through New → Preparing → Ready →
 * Picked up with the clock; what admins change (DemoState) overrides that.
 *
 * Order shape: id, branch, customer (index), placed_at, pickup_at (unix),
 * prep (minutes), items [[product id, qty, label?, unit price?]], total,
 * payment, status, rating, scheduled, plus status times for today's orders.
 * Orders placed during the day (website, demo buttons) may also carry
 * contact {name, phone}, notes and source, and wait at Ready until staff
 * confirm the pickup (manual_pickup).
 *
 * To go live, replace the calls to this class in App\Services\Dashboard\*
 * with Eloquent queries returning the same shapes.
 */
class DemoDataService
{
    private const VERSION = 'v3';

    /**
     * Order numbers: ORD-{year}-{day of year × 1000 + sequence}. Each branch
     * gets a block of 100 generated orders a day (0–99, 100–199, …); orders
     * placed during the day (website, demo buttons) start at 500.
     */
    private const PER_DAY = 1000;

    private const EXTRA_FROM = 500;

    private const RANK = ['pending' => 0, 'preparing' => 1, 'ready' => 2, 'completed' => 3, 'cancelled' => 4];

    private const HOURS = [6 => 2, 7 => 6, 8 => 10, 9 => 9, 10 => 6, 11 => 6, 12 => 9, 13 => 9, 14 => 6, 15 => 5, 16 => 7, 17 => 7, 18 => 5, 19 => 4, 20 => 3, 21 => 2, 22 => 1];

    private const WEEKDAY = [1 => 0.9, 2 => 0.92, 3 => 0.95, 4 => 1.0, 5 => 1.1, 6 => 1.3, 7 => 1.25];

    /** @var array<string, array> generated orders per "date|branch" for this request */
    private array $days = [];

    private ?array $productWeights = null;

    private ?array $pools = null;

    public function __construct(private DemoCatalog $catalog, private DemoState $state) {}

    /* ------------------------------------------------------------------ */
    /* Time                                                               */
    /* ------------------------------------------------------------------ */

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('dashboard.timezone'));
    }

    public function historyStart(): CarbonImmutable
    {
        return $this->now()->startOfDay()->subDays(config('dashboard.history_days') - 1);
    }

    /* ------------------------------------------------------------------ */
    /* Reference data                                                     */
    /* ------------------------------------------------------------------ */

    /** Branches with any edits made in the demo (status, name, phone, manager). */
    public function branches(): array
    {
        $edits = (array) $this->state->get('branch-edits', []);
        $created = (array) $this->state->get('branch-created', []);

        return collect($this->catalog->branches())
            ->merge($created)
            ->map(fn (array $b, string $key) => array_merge($b, $edits[$key] ?? []))
            ->map(fn (array $b, string $key) => [...$b, 'status' => $this->state->get("branch-status:$key", $b['status'] ?? 'open')])
            ->all();
    }

    public function branch(?string $key): ?array
    {
        return $key ? ($this->branches()[$key] ?? null) : null;
    }

    /** Categories in display order (a branch can reorder its own menu). */
    public function categories(?string $branch = null): array
    {
        $order = (array) $this->state->get('category-order:'.($branch ?? '*'), array_keys(DemoCatalog::CATEGORIES));

        return collect($order)
            ->merge(array_keys(DemoCatalog::CATEGORIES))
            ->unique()
            ->filter(fn ($key) => isset(DemoCatalog::CATEGORIES[$key]))
            ->mapWithKeys(fn ($key) => [$key => ['key' => $key, ...DemoCatalog::CATEGORIES[$key]]])
            ->all();
    }

    /** Products, with this branch's availability and today's stock level when a branch is given. */
    public function products(?string $branch = null): array
    {
        $products = $this->catalog->products();
        $hidden = (array) $this->state->get('products-hidden', []);

        foreach ($products as $id => $product) {
            $products[$id]['active'] = ! in_array($id, $hidden, true);
        }

        if (! $branch) {
            return $products;
        }

        $off = (array) $this->state->get("unavailable:$branch", []);
        $r = $this->rng('stock|'.$this->now()->toDateString().'|'.$branch);
        $ids = array_keys($products);
        $low = [$ids[$r->getInt(0, 12)], $ids[$r->getInt(13, 25)], $ids[$r->getInt(26, count($ids) - 1)]];
        $out = $ids[$r->getInt(13, count($ids) - 1)];

        foreach ($products as $id => $product) {
            $products[$id]['available'] = $product['active'] && ! in_array($id, $off, true) && $id !== $out;
            $products[$id]['stock'] = $id === $out ? 'out' : (in_array($id, $low, true) ? 'low' : 'ok');
            $products[$id]['left'] = $id === $out ? 0 : (in_array($id, $low, true) ? 2 + $id % 5 : null);
        }

        return $products;
    }

    public function customers(): array
    {
        return $this->catalog->customers();
    }

    /** Demo customer profile for a signed-in customer: the demo account is #0, others map by email. */
    public function customerIndexFor(string $email): int
    {
        return strtolower($email) === 'customer@test.com'
            ? 0
            : 1 + crc32(strtolower($email)) % (DemoCatalog::CUSTOMER_COUNT - 1);
    }

    /* ------------------------------------------------------------------ */
    /* Orders                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Orders whose pickup falls between $from and $to, oldest first.
     * Today's orders only appear once they have been placed.
     */
    public function orders(CarbonInterface $from, CarbonInterface $to, ?string $branch = null): array
    {
        $from = CarbonImmutable::instance($from)->max($this->historyStart());
        $to = CarbonImmutable::instance($to)->min($this->now()->endOfDay());
        $branches = $branch ? [$branch] : array_keys($this->branches());
        $result = [];

        for ($day = $from->startOfDay(); $day <= $to; $day = $day->addDay()) {
            foreach ($branches as $key) {
                foreach ($this->day($day, $key) as $order) {
                    if ($order['pickup_at'] >= $from->timestamp && $order['pickup_at'] <= $to->timestamp) {
                        $result[] = $order;
                    }
                }
            }
        }

        usort($result, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);

        return $result;
    }

    /** Orders in a dashboard date range (see DateRange::$placedBefore). */
    public function ordersIn(DateRange $range, ?string $branch = null): array
    {
        $orders = $this->orders($range->from, $range->to, $branch);

        return $range->placedBefore === null
            ? $orders
            : array_values(array_filter($orders, fn ($o) => $o['placed_at'] <= $range->placedBefore));
    }

    /** All generated history (for lifetime figures such as customer totals). */
    public function allOrders(?string $branch = null): array
    {
        return $this->orders($this->historyStart(), $this->now()->endOfDay(), $branch);
    }

    /**
     * Lifetime figures per customer: orders, spent, first/last order, home branch counts.
     * History is summarised once a day; today's orders are added live.
     */
    public function customerLedger(): array
    {
        $today = $this->now()->startOfDay();
        $ledger = $this->state->remember('ledger:'.self::VERSION.':'.$today->toDateString(), function () use ($today) {
            return $this->summarise($this->orders($this->historyStart(), $today->subSecond()));
        });

        foreach ($this->summarise($this->todayOrders()) as $id => $row) {
            if (! isset($ledger[$id])) {
                $ledger[$id] = $row;
                continue;
            }
            $ledger[$id]['orders'] += $row['orders'];
            $ledger[$id]['spent'] += $row['spent'];
            $ledger[$id]['last'] = $row['last'];
            foreach ($row['branches'] as $branch => $n) {
                $ledger[$id]['branches'][$branch] = ($ledger[$id]['branches'][$branch] ?? 0) + $n;
            }
        }

        return $ledger;
    }

    /** Every order of one customer, oldest first (history cached per day). */
    public function customerOrders(int $customer): array
    {
        $today = $this->now()->startOfDay();
        $past = $this->state->remember("customer-orders:".self::VERSION.":$customer:".$today->toDateString(), fn () => array_values(array_filter(
            $this->orders($this->historyStart(), $today->subSecond()),
            fn ($o) => $o['customer'] === $customer,
        )));

        return [...$past, ...array_values(array_filter($this->todayOrders(), fn ($o) => $o['customer'] === $customer))];
    }

    private function summarise(array $orders): array
    {
        $rows = [];
        foreach ($orders as $o) {
            if ($o['status'] === 'cancelled') {
                continue;
            }
            $row = &$rows[$o['customer']];
            $row['orders'] = ($row['orders'] ?? 0) + 1;
            $row['spent'] = ($row['spent'] ?? 0) + $o['total'];
            $row['first'] ??= $o['pickup_at'];
            $row['last'] = $o['pickup_at'];
            $row['branches'][$o['branch']] = ($row['branches'][$o['branch']] ?? 0) + 1;
            unset($row);
        }

        return $rows;
    }

    public function todayOrders(?string $branch = null): array
    {
        return $this->orders($this->now()->startOfDay(), $this->now()->endOfDay(), $branch);
    }

    /** One order by number. With $branch, another branch's order is simply not found. */
    public function findOrder(string $id, ?string $branch = null): ?array
    {
        if (! preg_match('/^ORD-(\d{4})-(\d{6})$/', $id, $m)) {
            return null;
        }

        $date = CarbonImmutable::create((int) $m[1], 1, 1, 0, 0, 0, config('dashboard.timezone'))->addDays(intdiv((int) $m[2], self::PER_DAY) - 1);
        if ($date < $this->historyStart() || $date > $this->now()) {
            return null;
        }

        foreach ($branch ? [$branch] : array_keys($this->branches()) as $key) {
            foreach ($this->day($date, $key) as $order) {
                if ($order['id'] === $id) {
                    return $order;
                }
            }
        }

        return null;
    }

    /**
     * Move an order to a new status (admin action). Returns the updated order.
     * $force also allows a step back (undoing a pickup confirmed by mistake).
     */
    public function setStatus(array $order, string $status, bool $force = false): array
    {
        $this->state->update('overrides:'.$this->now()->toDateString(), fn ($overrides) => [
            ...$overrides,
            $order['id'] => ['status' => $status, 'at' => $this->now()->timestamp, 'force' => $force],
        ], 2);
        $this->days = [];

        return $this->findOrder($order['id'], $order['branch']);
    }

    /**
     * Places an order now: from the website's cake order form, the Live Orders
     * "simulate" button, or the customer's demo order. Kept for the day in
     * DemoState. $extra adds contact, notes, source or a fixed payment method.
     */
    public function placeOrder(string $branch, ?int $customer = null, ?array $items = null, bool $silent = false, array $extra = []): array
    {
        $now = $this->now();
        $date = $now->toDateString();
        $extras = (array) $this->state->get("extra-orders:$date", []);
        $r = $this->rng('extra|'.$date.'|'.count($extras).'|'.$now->timestamp);

        $items ??= $this->randomItems($r, (int) $now->format('G'));
        $prep = $this->prepMinutes($r, count($items), (int) $now->format('G'), $branch);
        $placed = $silent ? $now->subMinutes(9) : $now;
        $order = [
            'id' => $this->orderNumber($now, self::EXTRA_FROM + count($extras)),
            'branch' => $branch,
            'customer' => $customer ?? $this->randomCustomer($r, $branch),
            'placed_at' => $placed->timestamp,
            'pickup_at' => $placed->addMinutes($prep + 12)->timestamp,
            'prep' => $prep,
            'items' => $items,
            'total' => $this->total($items),
            'payment' => $this->pick($r, array_map(fn ($p) => $p['share'], DemoCatalog::PAYMENTS)),
            'status' => 'completed',
            'rating' => null,
            'scheduled' => false,
            'manual_pickup' => true,
            ...$extra,
        ];

        $this->state->put("extra-orders:$date", [...$extras, $order], 2);
        $this->days = [];

        return $this->findOrder($order['id'], $branch);
    }

    /** Orders of one day at one branch, with live statuses for today. */
    private function day(CarbonImmutable $date, string $branch): array
    {
        $key = $date->toDateString().'|'.$branch;
        if (isset($this->days[$key])) {
            return $this->days[$key];
        }

        $today = $this->now()->startOfDay();
        if ($date->startOfDay() > $today || $date->startOfDay() < $this->historyStart()) {
            return $this->days[$key] = [];
        }

        if (! $date->isSameDay($today)) {
            // Past days never change: generate every branch once, then read from the demo store.
            $all = $this->state->remember('day:'.self::VERSION.':'.$date->toDateString(), fn () => collect($this->catalog->branches())
                ->map(fn ($b, $k) => array_map(fn ($o) => [...$o, 'status_at' => $o['pickup_at']], $this->generate($date->startOfDay(), $k)))
                ->all());

            foreach ($this->catalog->branches() as $k => $b) {
                $this->days[$date->toDateString().'|'.$k] = $all[$k] ?? [];
            }

            return $this->days[$key] ?? [];
        }

        $extras = array_filter((array) $this->state->get('extra-orders:'.$date->toDateString(), []), fn ($o) => $o['branch'] === $branch);
        $overrides = (array) $this->state->get('overrides:'.$date->toDateString(), []);
        $now = $this->now()->timestamp;

        return $this->days[$key] = array_values(array_map(
            fn ($o) => $this->live($o, $now, $overrides[$o['id']] ?? null),
            array_filter([...$this->generate($date->startOfDay(), $branch), ...$extras], fn ($o) => $o['placed_at'] <= $now),
        ));
    }

    /** Where an order of today stands right now: the kitchen's clock, unless an admin moved it further. */
    private function live(array $o, int $now, ?array $override): array
    {
        $accept = max($o['placed_at'] + 240, $o['pickup_at'] - $o['prep'] * 60 - 240);
        $ready = $o['pickup_at'] - 180;
        $collected = $o['pickup_at'] + (crc32($o['id']) % 14) * 60;

        if ($o['status'] === 'cancelled') {
            [$status, $at] = $now >= $o['placed_at'] + 480 ? ['cancelled', $o['placed_at'] + 480] : ['pending', $o['placed_at']];
        } elseif ($now < $accept) {
            [$status, $at] = ['pending', $o['placed_at']];
        } elseif ($now < $ready) {
            [$status, $at] = ['preparing', $accept];
        } elseif ($now < $collected || ! empty($o['manual_pickup'])) {
            // Orders placed today wait at the counter until staff confirm the pickup.
            [$status, $at] = ['ready', $ready];
        } else {
            [$status, $at] = ['completed', $collected];
        }

        if ($override && (! empty($override['force']) || $override['status'] === 'cancelled' || self::RANK[$override['status']] > self::RANK[$status])) {
            [$status, $at] = [$override['status'], $override['at']];
        }

        return [...$o, 'status' => $status, 'status_at' => $at, 'rating' => $status === 'completed' ? $o['rating'] : null];
    }

    /** Deterministic orders for one branch and day (all completed or cancelled). */
    private function generate(CarbonImmutable $date, string $branch): array
    {
        $profile = $this->catalog->branches()[$branch] ?? null;
        if (! $profile) {
            return [];
        }

        $r = $this->rng(self::VERSION.'|'.$date->toDateString().'|'.$branch);
        // Slow seasonal swings tied to the calendar date, so a day always generates the same orders.
        $t = $date->timestamp / 86400;
        $trend = 0.92 + 0.08 * sin($t / 23) + 0.06 * sin($t / 9.5);
        $count = min(98, (int) round($profile['volume'] * self::WEEKDAY[$date->dayOfWeekIso] * $trend * (0.85 + $this->float($r) * 0.3)));

        [$open, $close] = $profile['hours'][$date->dayOfWeekIso >= 6 ? 'weekend' : 'weekday'];
        $hours = array_filter(self::HOURS, fn ($h) => $h >= $open && $h < $close, ARRAY_FILTER_USE_KEY);

        $orders = [];
        for ($i = 0; $i < $count; $i++) {
            $orders[] = $this->makeOrder($r, $date, $branch, $this->pick($r, $hours), $this->randomCustomer($r, $branch));
        }

        // The demo customer (Selam) orders from her home café every few days, mostly breakfast.
        if ($branch === $this->catalog->customers()[0]['home'] && $this->float($r) < 0.34) {
            $orders[] = $this->makeOrder($r, $date, $branch, $r->getInt(7, 10), 0);
        }

        usort($orders, fn ($a, $b) => $a['placed_at'] <=> $b['placed_at']);
        $offset = array_search($branch, array_keys($this->catalog->branches()), true) * 100;

        foreach ($orders as $seq => &$order) {
            $order['id'] = $this->orderNumber($date, $offset + $seq);
        }

        return $orders;
    }

    private function makeOrder(Randomizer $r, CarbonImmutable $date, string $branch, int $hour, int $customer): array
    {
        $items = $this->randomItems($r, $hour);
        $prep = $this->prepMinutes($r, count($items), $hour, $branch);
        $pickup = $date->setTime($hour, $r->getInt(0, 59));
        $scheduled = $this->float($r) < 0.32;
        $lead = $scheduled ? $r->getInt(60, 300) : $prep + $r->getInt(6, 20);
        $cancelled = $this->float($r) < 0.04;

        return [
            'id' => null,
            'branch' => $branch,
            'customer' => $customer,
            'placed_at' => $pickup->subMinutes($lead)->timestamp,
            'pickup_at' => $pickup->timestamp,
            'prep' => $prep,
            'items' => $items,
            'total' => $this->total($items),
            'payment' => $this->pick($r, array_map(fn ($p) => $p['share'], DemoCatalog::PAYMENTS)),
            'status' => $cancelled ? 'cancelled' : 'completed',
            'rating' => ! $cancelled && $this->float($r) < 0.35 ? $this->pick($r, [5 => 55, 4 => 30, 3 => 11, 2 => 4]) : null,
            'scheduled' => $scheduled,
        ];
    }

    private function randomItems(Randomizer $r, int $hour): array
    {
        $lines = $this->pick($r, [1 => 38, 2 => 36, 3 => 18, 4 => 8]);
        $weights = $this->productWeights($hour);
        $items = [];

        while (count($items) < $lines) {
            $id = $this->pick($r, $weights);
            $items[$id] = [$id, ($items[$id][1] ?? 0) + ($this->float($r) < 0.8 ? 1 : 2)];
        }

        return array_values($items);
    }

    private function prepMinutes(Randomizer $r, int $lines, int $hour, string $branch): int
    {
        $rush = in_array($hour, [8, 9, 12, 13], true) ? 4 : 0;
        $kitchen = $this->catalog->branches()[$branch]['prep'] ?? 0;

        return max(6, min(34, (int) round(9 + $lines * 1.8 + $rush + $kitchen + $r->getInt(-3, 5))));
    }

    private function randomCustomer(Randomizer $r, string $branch): int
    {
        if ($this->pools === null) {
            $this->pools = ['*' => range(1, DemoCatalog::CUSTOMER_COUNT - 1)];
            foreach (array_slice($this->catalog->customers(), 1, null, true) as $c) {
                $this->pools[$c['home']][] = $c['id'];
            }
        }

        $pool = $this->float($r) < 0.85 && ! empty($this->pools[$branch]) ? $this->pools[$branch] : $this->pools['*'];

        // Skewed towards the front of the pool: those are the regulars.
        return $pool[(int) floor(count($pool) * $this->float($r) ** 1.7)];
    }

    private function productWeights(int $hour): array
    {
        return $this->productWeights[$hour] ??= array_map(
            fn ($p) => $p['popularity'] * (DemoCatalog::CATEGORY_HOURS[$p['category']][$hour] ?? 1),
            $this->catalog->products(),
        );
    }

    private function total(array $items): float
    {
        $products = $this->catalog->products();

        return (float) array_sum(array_map(fn ($line) => ($line[3] ?? $products[$line[0]]['price']) * $line[1], $items));
    }

    private function orderNumber(CarbonImmutable $date, int $seq): string
    {
        return sprintf('ORD-%d-%06d', $date->year, $date->dayOfYear * self::PER_DAY + $seq);
    }

    /* ------------------------------------------------------------------ */
    /* Randomness                                                         */
    /* ------------------------------------------------------------------ */

    private function rng(string $seed): Randomizer
    {
        return new Randomizer(new Mt19937(crc32($seed)));
    }

    private function float(Randomizer $r): float
    {
        return $r->getInt(0, 999_999) / 1_000_000;
    }

    /** Weighted pick: returns a key of $weights. */
    private function pick(Randomizer $r, array $weights): int|string
    {
        $x = $this->float($r) * array_sum($weights);
        foreach ($weights as $key => $weight) {
            if (($x -= $weight) < 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}

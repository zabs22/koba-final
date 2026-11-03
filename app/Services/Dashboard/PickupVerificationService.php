<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Demo\DemoState;
use App\Services\Demo\DemoDataService;
use App\Support\Dashboard\PickupCode;

/**
 * The counter's pickup check: find today's order by pickup code (typed or
 * scanned) within the admin's own branch, then confirm the hand-over.
 * Finding an order never completes it; only confirm() does.
 */
class PickupVerificationService
{
    /** How long a confirmed pickup can be undone (the screen shows 10 s). */
    public const UNDO_SECONDS = 15;

    public function __construct(
        private DemoDataService $data,
        private DemoState $state,
        private OrderPresenter $present,
    ) {}

    /**
     * @return array{state: string, code?: string, order?: array, branch?: string}
     *   state: found | not_ready | already_picked_up | cancelled | wrong_branch | not_found | invalid
     */
    public function lookup(string $branch, string $input): array
    {
        $code = PickupCode::normalize($input);
        if (! $code) {
            return ['state' => 'invalid'];
        }

        if ($order = $this->find($branch, $code)) {
            $state = match ($order['status']) {
                'ready' => 'found',
                'completed' => 'already_picked_up',
                'cancelled' => 'cancelled',
                default => 'not_ready',
            };

            return ['state' => $state, 'code' => $code, 'order' => $this->result($order)];
        }

        // Another café's code: say where to collect it, but show nothing of the order.
        foreach ($this->data->branches() as $key => $other) {
            if ($key !== $branch && $this->find($key, $code)) {
                return ['state' => 'wrong_branch', 'code' => $code, 'branch' => $other['name']];
            }
        }

        return ['state' => 'not_found', 'code' => $code];
    }

    /** Today's order at this branch with this code. */
    public function find(string $branch, string $code): ?array
    {
        if (strlen($code) !== 9 || PickupCode::letter($branch) !== $code[3]) {
            return null;
        }

        foreach ($this->data->todayOrders($branch) as $order) {
            if (PickupCode::for($order) === $code) {
                return $order;
            }
        }

        return null;
    }

    public function confirm(User $user, array $order, string $via): array
    {
        abort_unless($order['status'] === 'ready', 422, "{$order['id']} is not ready for pickup.");

        $updated = $this->data->setStatus($order, 'completed');
        $this->state->update($this->logKey(), fn ($log) => [
            ...$log,
            $order['id'] => ['branch' => $order['branch'], 'at' => $this->data->now()->timestamp, 'by' => $user->name, 'user' => $user->id, 'via' => $via],
        ], 2);

        return $this->result($updated);
    }

    /** Puts an order confirmed by mistake back to Ready, shortly after confirming. */
    public function undo(User $user, array $order): array
    {
        $entry = $this->log()[$order['id']] ?? null;
        abort_unless(
            $entry && $entry['user'] === $user->id && $order['status'] === 'completed' && $this->data->now()->timestamp - $entry['at'] <= self::UNDO_SECONDS,
            422,
            'This pickup can no longer be undone.',
        );

        $updated = $this->data->setStatus($order, 'ready', force: true);
        $this->forget([$order['id']]);

        return $this->result($updated);
    }

    /** Demo: every pickup confirmed at this counter today goes back to Ready. */
    public function reset(string $branch): int
    {
        $ids = array_keys(array_filter($this->log(), fn ($e) => $e['branch'] === $branch));
        foreach ($ids as $id) {
            if ($order = $this->data->findOrder($id, $branch)) {
                $this->data->setStatus($order, 'ready', force: true);
            }
        }
        $this->forget($ids);

        return count($ids);
    }

    /** What the verification page shows about an order. */
    public function result(array $order): array
    {
        $detail = $this->present->detail($order);
        $now = $this->data->now()->timestamp;
        $minutes = (int) round(($now - $order['pickup_at']) / 60);
        $entry = $this->log()[$order['id']] ?? null;

        return [
            ...$detail,
            'timing' => match (true) {
                $minutes < -5 => ['key' => 'early', 'label' => abs($minutes).' min early'],
                $minutes > 5 => ['key' => 'late', 'label' => $minutes.' min late'],
                default => ['key' => 'on_time', 'label' => 'On time'],
            },
            'payment_note' => $order['payment'] === 'cash' ? 'Pay at pickup (demo)' : 'Paid online (demo)',
            'picked_up' => $order['status'] === 'completed' ? [
                'time' => $this->present->time($entry['at'] ?? $order['status_at'] ?? $order['pickup_at']),
                'by' => $entry['by'] ?? 'Kitchen board',
                'via' => $entry['via'] ?? 'board',
            ] : null,
            'can_undo' => $entry && $now - $entry['at'] <= self::UNDO_SECONDS,
        ];
    }

    /** Ready list, counters, recent confirmations and the demo scan scenarios. */
    public function feed(string $branch): array
    {
        $today = $this->data->todayOrders($branch);
        $now = $this->data->now()->timestamp;
        $by = fn (string $status) => array_values(array_filter($today, fn ($o) => $o['status'] === $status));
        $ready = $by('ready');
        usort($ready, fn ($a, $b) => $a['pickup_at'] <=> $b['pickup_at']);
        $completed = $by('completed');

        $recent = [];
        foreach (array_reverse($this->log(), true) as $id => $entry) {
            if ($entry['branch'] === $branch && ($order = $this->data->findOrder($id, $branch))) {
                $row = $this->present->row($order);
                $recent[] = ['id' => $id, 'customer' => $row['customer'], 'initials' => $row['initials'], 'customer_id' => $row['customer_id'], 'time' => $this->present->time($entry['at']), 'by' => $entry['by'], 'via' => $entry['via']];
            }
        }

        return [
            'ready' => array_map(fn ($o) => $this->present->row($o), array_slice($ready, 0, 12)),
            'counts' => [
                'ready' => count($ready),
                'picked_up' => count($completed),
                'late' => count(array_filter($ready, fn ($o) => $o['pickup_at'] < $now)),
            ],
            'recent' => array_slice($recent, 0, 8),
            'scenarios' => $this->scenarios($branch, $today, $ready, $completed),
            'now' => $now,
        ];
    }

    /** One real code per screen state, for the demo panel's simulated scan. */
    private function scenarios(string $branch, array $today, array $ready, array $completed): array
    {
        $pick = fn (array $orders) => $orders ? PickupCode::for($orders[0]) : null;
        $web = array_values(array_filter($ready, fn ($o) => ! empty($o['manual_pickup'])));
        usort($completed, fn ($a, $b) => $b['status_at'] <=> $a['status_at']);
        $other = collect($this->data->branches())->keys()->first(fn ($key) => $key !== $branch);

        $unknown = 'KB-'.PickupCode::letter($branch).'ZZZZZ';
        if ($this->find($branch, $unknown)) {
            $unknown = 'KB-'.PickupCode::letter($branch).'ZZZZY';
        }

        return [
            ['key' => 'found', 'label' => 'Ready for pickup', 'code' => $pick($web ?: $ready)],
            ['key' => 'already_picked_up', 'label' => 'Already picked up', 'code' => $pick($completed)],
            ['key' => 'not_ready', 'label' => 'Still preparing', 'code' => $pick(array_values(array_filter($today, fn ($o) => in_array($o['status'], ['pending', 'preparing'], true))))],
            ['key' => 'wrong_branch', 'label' => 'Another branch', 'code' => $other ? $pick($this->data->todayOrders($other)) : null],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'code' => $pick(array_values(array_filter($today, fn ($o) => $o['status'] === 'cancelled')))],
            ['key' => 'not_found', 'label' => 'Unknown code', 'code' => $unknown],
        ];
    }

    private function log(): array
    {
        return (array) $this->state->get($this->logKey(), []);
    }

    private function forget(array $ids): void
    {
        $this->state->update($this->logKey(), fn ($log) => array_diff_key($log, array_flip($ids)), 2);
    }

    private function logKey(): string
    {
        return 'pickups:'.$this->data->now()->toDateString();
    }
}

<?php

namespace App\Support\Dashboard;

use App\Services\Demo\DemoCatalog;

/**
 * Small, pure aggregations over order arrays (see DemoDataService for the shape).
 */
final class Metrics
{
    public static function valid(array $orders): array
    {
        return array_values(array_filter($orders, fn ($o) => $o['status'] !== 'cancelled'));
    }

    public static function revenue(array $orders): float
    {
        return (float) array_sum(array_column(self::valid($orders), 'total'));
    }

    public static function aov(array $orders): float
    {
        $valid = self::valid($orders);

        return $valid ? self::revenue($valid) / count($valid) : 0.0;
    }

    /** Average minutes from placing to ready, over finished orders. */
    public static function avgPrep(array $orders): float
    {
        $done = array_filter($orders, fn ($o) => in_array($o['status'], ['ready', 'completed'], true));

        return $done ? round(array_sum(array_column($done, 'prep')) / count($done), 1) : 0.0;
    }

    public static function cancellationRate(array $orders): float
    {
        return $orders ? round(count(array_filter($orders, fn ($o) => $o['status'] === 'cancelled')) / count($orders) * 100, 1) : 0.0;
    }

    public static function rating(array $orders): ?float
    {
        $ratings = array_filter(array_column($orders, 'rating'));

        return $ratings ? round(array_sum($ratings) / count($ratings), 1) : null;
    }

    /** Percent change, or null when there is nothing to compare with. */
    public static function change(float $current, float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    /**
     * One value per range bucket (by pickup time).
     *
     * @param  callable(array $orders): float|int  $value
     */
    public static function series(array $orders, array $buckets, ?callable $value = null): array
    {
        $value ??= fn (array $orders) => count($orders);
        $grouped = array_fill(0, count($buckets), []);
        $i = 0;

        foreach ($orders as $o) {
            while ($i < count($buckets) && $o['pickup_at'] > $buckets[$i]['to']) {
                $i++;
            }
            if ($i < count($buckets) && $o['pickup_at'] >= $buckets[$i]['from']) {
                $grouped[$i][] = $o;
            }
        }

        return array_map(fn ($group) => round((float) $value($group), 2), $grouped);
    }

    public static function revenueSeries(array $orders, array $buckets): array
    {
        return self::series($orders, $buckets, fn ($g) => self::revenue($g));
    }

    public static function statusCounts(array $orders): array
    {
        $counts = array_fill_keys(DemoCatalog::STATUSES, 0);
        foreach ($orders as $o) {
            $counts[$o['status']]++;
        }

        return $counts;
    }

    public static function paymentSplit(array $orders): array
    {
        $split = array_fill_keys(array_keys(DemoCatalog::PAYMENTS), 0.0);
        foreach (self::valid($orders) as $o) {
            $split[$o['payment']] += $o['total'];
        }

        return $split;
    }

    /** @return array<int, array{units: int, revenue: float}> keyed by product id, best sellers first */
    public static function products(array $orders): array
    {
        $rows = [];
        foreach (self::valid($orders) as $o) {
            foreach ($o['items'] as [$id, $qty]) {
                $rows[$id]['units'] = ($rows[$id]['units'] ?? 0) + $qty;
                $rows[$id]['orders'] = ($rows[$id]['orders'] ?? 0) + 1;
            }
        }

        uasort($rows, fn ($a, $b) => $b['units'] <=> $a['units']);

        return $rows;
    }

    /** Revenue per category key, largest first. */
    public static function categories(array $orders, array $products): array
    {
        $rows = array_fill_keys(array_keys(DemoCatalog::CATEGORIES), 0.0);
        foreach (self::valid($orders) as $o) {
            foreach ($o['items'] as $line) {
                [$id, $qty] = $line;
                $rows[$products[$id]['category']] += ($line[3] ?? $products[$id]['price']) * $qty;
            }
        }
        arsort($rows);

        return $rows;
    }

    /** Orders by weekday (Mon–Sun) × hour (06–22): ApexCharts heatmap series. */
    public static function heatmap(array $orders, string $timezone): array
    {
        $grid = array_fill(1, 7, array_fill(6, 17, 0));
        foreach ($orders as $o) {
            $t = (new \DateTimeImmutable('@'.$o['pickup_at']))->setTimezone(new \DateTimeZone($timezone));
            $h = (int) $t->format('G');
            if ($h >= 6 && $h <= 22) {
                $grid[(int) $t->format('N')][$h]++;
            }
        }

        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        // Sunday at the top so the week reads downwards from Monday.
        return array_reverse(array_map(fn ($d) => [
            'name' => $days[$d],
            'data' => array_map(fn ($h) => ['x' => sprintf('%02d', $h), 'y' => $grid[$d][$h]], range(6, 22)),
        ], array_keys($days)));
    }

    public static function kpi(string $label, float $value, ?float $previous, string $format, string $icon, ?array $spark = null, bool $lowerIsBetter = false): array
    {
        return [
            'label' => $label,
            'value' => round($value, 2),
            'change' => $previous === null ? null : self::change($value, $previous),
            'lowerIsBetter' => $lowerIsBetter,
            'format' => $format,
            'icon' => $icon,
            'spark' => $spark,
        ];
    }
}

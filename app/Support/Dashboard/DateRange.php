<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The dashboards' date filter: Today, 7 days, 30 days, This month or Custom,
 * with the matching previous period and the chart buckets (hours or days).
 */
final class DateRange
{
    public const OPTIONS = ['today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'month' => 'This month', 'custom' => 'Custom'];

    /**
     * @param  int|null  $placedBefore  only orders placed before this moment (used to compare "today so far" fairly)
     */
    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?int $placedBefore = null,
    ) {}

    public static function fromRequest(Request $request, string $default = '7d'): self
    {
        $key = (string) $request->query('range', $default);

        return self::make(array_key_exists($key, self::OPTIONS) ? $key : $default, $request->query('from'), $request->query('to'));
    }

    public static function make(string $key, ?string $from = null, ?string $to = null): self
    {
        $now = CarbonImmutable::now(config('dashboard.timezone'));
        $today = $now->startOfDay();

        return match ($key) {
            'today' => new self('today', $today, $today->endOfDay()),
            '30d' => new self('30d', $today->subDays(29), $today->endOfDay()),
            'month' => new self('month', $today->startOfMonth(), $today->endOfDay()),
            'custom' => self::custom($from, $to, $today),
            default => new self('7d', $today->subDays(6), $today->endOfDay()),
        };
    }

    private static function custom(?string $from, ?string $to, CarbonImmutable $today): self
    {
        $tz = config('dashboard.timezone');
        $max = intdiv(config('dashboard.history_days'), 2);

        try {
            $end = $to ? CarbonImmutable::parse($to, $tz)->endOfDay()->min($today->endOfDay()) : $today->endOfDay();
            $start = $from ? CarbonImmutable::parse($from, $tz)->startOfDay() : $end->subDays(13)->startOfDay();
        } catch (\Throwable) {
            return self::make('7d');
        }

        $start = $start->max($end->subDays($max - 1)->startOfDay())->min($end->startOfDay());

        return new self('custom', $start, $end);
    }

    /** The same length of time immediately before. "Today" compares with what had been ordered by this time yesterday. */
    public function previous(): self
    {
        if ($this->key === 'today') {
            $now = CarbonImmutable::now(config('dashboard.timezone'));

            return new self('previous', $this->from->subDay(), $this->from->subSecond(), $now->subDay()->timestamp);
        }

        $days = $this->days();

        return new self('previous', $this->from->subDays($days), $this->from->subSecond());
    }

    public function days(): int
    {
        return (int) round($this->from->diffInDays($this->to->addSecond()));
    }

    public function isLive(): bool
    {
        return $this->to->isFuture() || $this->to->isToday();
    }

    public function hourly(): bool
    {
        return $this->days() <= 1;
    }

    /**
     * Chart buckets: hours 06–22 for a single day, otherwise days.
     *
     * @return list<array{label: string, from: int, to: int}>
     */
    public function buckets(): array
    {
        $buckets = [];

        if ($this->hourly()) {
            foreach (range(6, 22) as $hour) {
                $start = $this->from->setTime($hour, 0);
                $buckets[] = ['label' => $start->format('H:i'), 'from' => $hour === 6 ? $this->from->timestamp : $start->timestamp, 'to' => $hour === 22 ? $this->to->timestamp : $start->addHour()->timestamp - 1];
            }

            return $buckets;
        }

        for ($day = $this->from; $day <= $this->to; $day = $day->addDay()) {
            $buckets[] = ['label' => $day->format($this->days() <= 7 ? 'D j' : 'M j'), 'from' => $day->timestamp, 'to' => $day->endOfDay()->timestamp];
        }

        return $buckets;
    }

    public function label(): string
    {
        return match ($this->key) {
            'today' => 'Today',
            'month' => $this->from->format('F Y'),
            default => $this->from->format('M j').' – '.$this->to->format('M j, Y'),
        };
    }

    public function previousLabel(): string
    {
        return match ($this->key) {
            'today' => 'vs yesterday',
            'month' => 'vs last month',
            default => 'vs previous '.$this->days().' days',
        };
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'label' => $this->label(),
            'compare' => $this->previousLabel(),
            'live' => $this->isLive(),
        ];
    }
}

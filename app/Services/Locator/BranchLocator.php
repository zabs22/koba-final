<?php

namespace App\Services\Locator;

use App\Services\Demo\DemoCatalog;
use App\Services\Demo\DemoDataService;
use App\Support\Geo\Geo;
use App\Support\Geo\HexGrid;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Which KOBA is nearest to me?", ranked the way delivery apps rank stores:
 *
 *  1. Great-circle distance to every café: cheap, and it tells us whether the
 *     visitor is in Addis Ababa at all (no point asking for driving times from Amsterdam).
 *  2. Real road distance and driving time to the closest candidates, in a single
 *     routing "table" request made from the centre of the visitor's hexagon cell
 *     (HexGrid), cached per cell so nearby visitors share one lookup. The road
 *     line for the map comes from route(), requested separately.
 *  3. Addis traffic on top of OSRM's empty-road times, by hour of day.
 *
 * The closest café by road wins, not the closest as the crow flies. When routing
 * is unavailable an estimate from the straight line keeps the page useful.
 * The visitor's location is used for this request only and never stored.
 */
class BranchLocator
{
    /** Cafés sent to the routing service per lookup; the rest are estimated. */
    private const CANDIDATES = 6;

    public function __construct(private DemoDataService $data) {}

    /** Cafés with coordinates, as the page and the API present them. */
    public function branches(): array
    {
        $now = $this->now();

        return collect($this->data->branches())
            ->filter(fn (array $b) => ! empty($b['coords']))
            ->map(fn (array $b) => $this->present($b, $now))
            ->values()
            ->all();
    }

    public function nearest(float $lat, float $lng, ?float $accuracy = null, ?string $purpose = null): array
    {
        $branches = array_map(function (array $b) use ($lat, $lng) {
            $bearing = Geo::bearing($lat, $lng, ...$b['coords']);

            return [...$b, 'straight_m' => (int) round(Geo::distance($lat, $lng, ...$b['coords'])), 'bearing' => (int) round($bearing), 'compass' => Geo::compass($bearing)];
        }, $this->branches());
        usort($branches, fn ($a, $b) => $a['straight_m'] <=> $b['straight_m']);

        $inArea = $branches && $branches[0]['straight_m'] <= config('koba.locator.service_radius_km') * 1000;
        $grid = HexGrid::fromConfig();
        $cell = $inArea ? $grid->cell($lat, $lng) : null;
        $roads = $cell ? $this->table($cell, array_slice($branches, 0, self::CANDIDATES)) : null;
        $traffic = $this->traffic();

        $branches = array_map(function (array $b) use ($roads, $traffic) {
            $road = $roads[$b['key']] ?? null;
            $roadM = $road['distance'] ?? $b['straight_m'] * config('koba.locator.circuity');

            return [
                ...$b,
                'estimated' => $road === null,
                'road_m' => (int) round($roadM),
                'drive_s' => (int) round($road ? $road['duration'] * $traffic : $roadM / (config('koba.locator.city_kmh') / 3.6)),
                'walk_s' => (int) round($roadM / (config('koba.locator.walk_kmh') / 3.6)),
            ];
        }, $branches);

        if ($inArea) {
            usort($branches, fn ($a, $b) => $a['drive_s'] <=> $b['drive_s']);
        }

        // A cake order needs a café that is taking pre-orders right now.
        $recommended = collect($branches)->first(fn ($b) => $purpose !== 'order' || $b['takes_orders']) ?? ($branches[0] ?? null);

        return [
            'origin' => [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $accuracy,
                'cell' => $cell ? ['id' => $cell['id'], 'polygon' => $cell['polygon']] : null,
                'ring' => $cell ? array_column($grid->ring($cell, 2), 'polygon') : [],
            ],
            'in_area' => $inArea,
            'source' => $roads ? 'roads' : 'estimate',
            'traffic' => $traffic,
            'nearest' => $branches[0]['key'] ?? null,
            'recommended' => $recommended['key'] ?? null,
            'branches' => $branches,
        ];
    }

    /**
     * The road from the visitor to one café, for the line on the map. Asked for
     * separately (while the map flies there), so a lookup never sends two
     * requests at once to a routing server that allows about one a second.
     */
    public function route(float $lat, float $lng, string $key): ?array
    {
        $branch = collect($this->branches())->firstWhere('key', $key);
        if (! $branch) {
            return null;
        }

        $points = sprintf('%.6f,%.6f;%.6f,%.6f', $lng, $lat, $branch['coords'][1], $branch['coords'][0]);
        $route = $this->osrm("route/v1/driving/$points", ['overview' => 'full', 'geometries' => 'geojson'])['routes'][0] ?? null;

        return $route ? [
            'branch' => $key,
            'coordinates' => $route['geometry']['coordinates'],
            'road_m' => (int) round($route['distance']),
            'drive_s' => (int) round($route['duration'] * $this->traffic()),
        ] : null;
    }

    /** Road distance and empty-road time from the cell centre to each café, cached per cell. */
    private function table(array $cell, array $branches): ?array
    {
        $key = 'locator:table:'.$cell['id'].':'.md5(json_encode(array_column($branches, 'coords', 'key')));
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $points = implode(';', array_map(fn ($p) => sprintf('%.6f,%.6f', $p[1], $p[0]), [$cell['center'], ...array_column($branches, 'coords')]));
        $table = $this->osrm("table/v1/driving/$points", ['sources' => 0, 'annotations' => 'duration,distance']);
        if (! $table) {
            return null;
        }

        $roads = [];
        foreach (array_values($branches) as $i => $b) {
            $duration = $table['durations'][0][$i + 1] ?? null;
            $distance = $table['distances'][0][$i + 1] ?? null;
            if ($duration !== null && $distance !== null) {
                $roads[$b['key']] = ['duration' => (float) $duration, 'distance' => (float) $distance];
            }
        }

        if ($roads) {
            Cache::put($key, $roads, now()->addMinutes(config('koba.locator.cache_minutes')));
        }

        return $roads ?: null;
    }

    private function osrm(string $path, array $query): ?array
    {
        try {
            $response = Http::timeout(config('koba.locator.timeout'))
                ->withUserAgent('KOBA-cafe-locator/1.0 (+'.config('app.url').')')
                ->acceptJson()
                // The public OSRM server allows about one request a second and is sometimes slow:
                // retry once after a pause when it is busy or doesn't answer in time.
                ->retry(2, 900, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->status() === 429), throw: false)
                ->get(rtrim(config('koba.locator.routing_url'), '/').'/'.$path, $query);

            if ($response->successful() && $response->json('code') === 'Ok') {
                return $response->json();
            }
            Log::notice('Café locator: routing answered HTTP '.$response->status().', using estimates.');

            return null;
        } catch (\Throwable $e) {
            // Never log the URL: it holds the visitor's coordinates.
            Log::notice('Café locator: routing unavailable ('.class_basename($e).'), using estimates.');

            return null;
        }
    }

    /** Multiplier on empty-road times for Addis traffic now. */
    private function traffic(): float
    {
        $hour = $this->now()->hour;
        foreach (config('koba.locator.peak_hours') as [$from, $to]) {
            if ($hour >= $from && $hour < $to) {
                return (float) config('koba.locator.traffic.peak');
            }
        }

        return (float) config('koba.locator.traffic.'.($hour >= 6 && $hour < 21 ? 'day' : 'night'));
    }

    private function present(array $b, CarbonImmutable $now): array
    {
        $hoursFor = fn (CarbonImmutable $day) => $b['hours'][$day->isWeekend() ? 'weekend' : 'weekday'];
        [$opens, $closes] = $hoursFor($now);
        $hour = $now->hour + $now->minute / 60;
        $openNow = $hour >= $opens && $hour < $closes;
        $next = $hour < $opens ? $opens : $hoursFor($now->addDay())[0];
        $labels = trans('koba.locations.atlas');

        return [
            'key' => $b['key'],
            'name' => $b['name'],
            'short' => $b['short'],
            'area' => $b['area'],
            'mood' => $b['mood'],
            'phone' => $b['phone'],
            'coords' => [(float) $b['coords'][0], (float) $b['coords'][1]],
            'image' => DemoCatalog::image($b['image']),
            'link' => $b['link'] ?? $b['maps'],
            'directions' => 'https://www.google.com/maps/dir/?api=1&destination='.$b['coords'][0].','.$b['coords'][1],
            'status' => $b['status'],
            'takes_orders' => $b['status'] === 'open',
            'open_now' => $openNow,
            'hours' => $openNow
                ? $labels['open_now'].' · '.str_replace(':time', self::clock($closes), $labels['until'])
                : $labels['closed'].' · '.str_replace(':time', self::clock($next), $labels['opens']),
        ];
    }

    /** 21 → "9:00 PM", the way the Locations page writes hours. */
    private static function clock(int $hour): string
    {
        return ((($hour + 11) % 12) + 1).':00 '.($hour >= 12 && $hour < 24 ? 'PM' : 'AM');
    }

    private function now(): CarbonImmutable
    {
        return $this->data->now();
    }
}

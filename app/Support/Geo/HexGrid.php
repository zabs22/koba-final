<?php

namespace App\Support\Geo;

/**
 * Hexagonal cells over one city, the idea behind Uber's H3: every cell has six
 * neighbours at the same distance, so "the cells around me" is a clean ring.
 *
 * Cells live on a flat (equirectangular) projection around the city origin,
 * which stays within a few metres across Addis Ababa and needs no library.
 * If KOBA spreads across cities, swap this for H3 proper (same interface).
 *
 * Pointy-top hexagons in axial coordinates (q, r); see redblobgames.com/grids/hexagons.
 */
final class HexGrid
{
    private const SQRT3 = 1.7320508075688772;

    private readonly float $mPerDegLat;

    private readonly float $mPerDegLng;

    /** @param  float  $size  circumradius in metres (centre to corner) */
    public function __construct(private readonly float $originLat, private readonly float $originLng, private readonly float $size)
    {
        $phi = deg2rad($originLat);
        // WGS84 metres per degree at the origin's latitude.
        $this->mPerDegLat = 111_132.954 - 559.822 * cos(2 * $phi) + 1.175 * cos(4 * $phi);
        $this->mPerDegLng = 111_412.84 * cos($phi) - 93.5 * cos(3 * $phi);
    }

    public static function fromConfig(): self
    {
        [$lat, $lng] = config('koba.locator.grid.origin');

        return new self($lat, $lng, (float) config('koba.locator.grid.size_m'));
    }

    /**
     * @return array{id: string, q: int, r: int, center: array{0: float, 1: float}, polygon: list<array{0: float, 1: float}>}
     *   polygon is GeoJSON order ([lng, lat]) and closed, ready for the map.
     */
    public function cell(float $lat, float $lng): array
    {
        [$x, $y] = $this->project($lat, $lng);
        [$q, $r] = $this->round((self::SQRT3 / 3 * $x - $y / 3) / $this->size, (2 / 3 * $y) / $this->size);

        return $this->describe($q, $r);
    }

    /** All cells within $k steps of $cell (itself included): 1 + 3k(k + 1) cells. */
    public function ring(array $cell, int $k = 1): array
    {
        $cells = [];
        for ($dq = -$k; $dq <= $k; $dq++) {
            for ($dr = max(-$k, -$dq - $k); $dr <= min($k, -$dq + $k); $dr++) {
                $cells[] = $this->describe($cell['q'] + $dq, $cell['r'] + $dr);
            }
        }

        return $cells;
    }

    private function describe(int $q, int $r): array
    {
        $x = $this->size * (self::SQRT3 * $q + self::SQRT3 / 2 * $r);
        $y = $this->size * 1.5 * $r;

        $polygon = [];
        for ($i = 0; $i <= 6; $i++) {
            $angle = deg2rad(60 * ($i % 6) - 30);
            [$plat, $plng] = $this->unproject($x + $this->size * cos($angle), $y + $this->size * sin($angle));
            $polygon[] = [round($plng, 6), round($plat, 6)];
        }

        [$clat, $clng] = $this->unproject($x, $y);

        return ['id' => "$q:$r", 'q' => $q, 'r' => $r, 'center' => [round($clat, 6), round($clng, 6)], 'polygon' => $polygon];
    }

    /** Fractional axial → the hexagon that contains it (cube rounding). */
    private function round(float $q, float $r): array
    {
        $s = -$q - $r;
        [$rq, $rr, $rs] = [round($q), round($r), round($s)];
        [$dq, $dr, $ds] = [abs($rq - $q), abs($rr - $r), abs($rs - $s)];

        if ($dq > $dr && $dq > $ds) {
            $rq = -$rr - $rs;
        } elseif ($dr > $ds) {
            $rr = -$rq - $rs;
        }

        return [(int) $rq, (int) $rr];
    }

    private function project(float $lat, float $lng): array
    {
        return [($lng - $this->originLng) * $this->mPerDegLng, ($lat - $this->originLat) * $this->mPerDegLat];
    }

    private function unproject(float $x, float $y): array
    {
        return [$this->originLat + $y / $this->mPerDegLat, $this->originLng + $x / $this->mPerDegLng];
    }
}

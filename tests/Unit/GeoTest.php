<?php

namespace Tests\Unit;

use App\Support\Geo\Geo;
use App\Support\Geo\HexGrid;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    private const MESKEL = [9.0106, 38.7613];

    public function test_distance_matches_the_length_of_a_degree(): void
    {
        // One degree along the equator: 2πR / 360.
        $this->assertEqualsWithDelta(2 * M_PI * Geo::EARTH_RADIUS_M / 360, Geo::distance(0, 0, 0, 1), 0.01);
        $this->assertSame(0.0, Geo::distance(...self::MESKEL, ...self::MESKEL));
    }

    public function test_bearing_and_compass(): void
    {
        $this->assertEqualsWithDelta(0, Geo::bearing(0, 0, 1, 0), 1e-9);
        $this->assertEqualsWithDelta(90, Geo::bearing(0, 0, 0, 1), 1e-9);
        $this->assertEqualsWithDelta(180, Geo::bearing(1, 0, 0, 0), 1e-9);
        $this->assertSame('north-east', Geo::compass(40));
        $this->assertSame('north', Geo::compass(350));
    }

    public function test_a_point_falls_in_the_hexagon_around_it(): void
    {
        $grid = new HexGrid(...[...self::MESKEL, 200]);

        foreach ([[9.0142, 38.7655], [9.0377, 38.7739], [8.9992, 38.7741], [9.0525, 38.7461]] as [$lat, $lng]) {
            $cell = $grid->cell($lat, $lng);
            $this->assertLessThanOrEqual(200.5, Geo::distance($lat, $lng, ...$cell['center']), 'Inside the circumradius of its cell');
            $this->assertSame($cell['id'], $grid->cell(...$cell['center'])['id'], 'The centre belongs to the same cell');
            $this->assertCount(7, $cell['polygon']);
            $this->assertSame($cell['polygon'][0], $cell['polygon'][6], 'Closed ring, GeoJSON style');
        }

        $this->assertSame('0:0', $grid->cell(...self::MESKEL)['id'], 'The origin is cell 0:0');
    }

    public function test_rings_have_six_equidistant_neighbours(): void
    {
        $grid = new HexGrid(...[...self::MESKEL, 200]);
        $cell = $grid->cell(9.02, 38.77);

        $this->assertCount(7, $grid->ring($cell, 1));
        $this->assertCount(19, $grid->ring($cell, 2));

        foreach ($grid->ring($cell, 1) as $neighbour) {
            if ($neighbour['id'] !== $cell['id']) {
                // Centre to centre of adjacent pointy hexagons: √3 × circumradius.
                $this->assertEqualsWithDelta(200 * sqrt(3), Geo::distance(...[...$cell['center'], ...$neighbour['center']]), 1.5);
            }
        }
    }
}

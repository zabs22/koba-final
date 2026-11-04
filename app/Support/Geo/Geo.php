<?php

namespace App\Support\Geo;

/**
 * Great-circle maths on [lat, lng] pairs (degrees).
 */
final class Geo
{
    /** Mean Earth radius (IUGG), metres. */
    public const EARTH_RADIUS_M = 6_371_008.8;

    private const COMPASS = ['north', 'north-east', 'east', 'south-east', 'south', 'south-west', 'west', 'north-west'];

    /** Haversine distance in metres; within ~0.5 % anywhere, plenty for ranking cafés. */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $a = sin(($phi2 - $phi1) / 2) ** 2 + cos($phi1) * cos($phi2) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 2 * self::EARTH_RADIUS_M * asin(min(1, sqrt($a)));
    }

    /** Initial bearing from the first point to the second: 0° = north, 90° = east. */
    public static function bearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dLng = deg2rad($lng2 - $lng1);
        $theta = atan2(sin($dLng) * cos($phi2), cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($dLng));

        return fmod(rad2deg($theta) + 360, 360);
    }

    /** "north-east" and friends, for "KOBA Atlas is 2 km south-east of you". */
    public static function compass(float $bearing): string
    {
        return self::COMPASS[(int) round($bearing / 45) % 8];
    }
}

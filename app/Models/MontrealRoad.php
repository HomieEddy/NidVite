<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MontrealRoad extends Model
{
    use HasFactory;

    protected $table = 'montreal_roads';

    protected $fillable = [
        'name',
        'source',
    ];

    public static function distanceToNearestMeters(float $latitude, float $longitude, ?int $radiusMeters = null): ?float
    {
        $radius = $radiusMeters ?? (int) config('report_validation.road_search_radius_meters', 250);

        $result = DB::selectOne(
            'SELECT ST_Distance(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) AS distance
            FROM montreal_roads
            WHERE ST_DWithin(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
            )
            ORDER BY distance ASC
            LIMIT 1',
            [$longitude, $latitude, $longitude, $latitude, $radius]
        );

        if ($result === null || $result->distance === null) {
            return null;
        }

        return (float) $result->distance;
    }
}

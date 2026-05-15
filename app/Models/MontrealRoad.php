<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MontrealRoad extends Model
{
    use HasFactory;

    protected $table = 'montreal_roads';

    protected $fillable = [
        'name',
        'source',
    ];

    public function scopeWithNearestDistance(Builder $query, float $latitude, float $longitude, ?int $radiusMeters = null): Builder
    {
        $radius = $radiusMeters ?? (int) config('report_validation.road_search_radius_meters', 250);

        return $query
            ->select('montreal_roads.*')
            ->selectRaw(
                'ST_Distance(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance',
                [$longitude, $latitude]
            )
            ->whereRaw(
                'ST_DWithin(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$longitude, $latitude, $radius]
            )
            ->orderBy('distance');
    }

    public static function distanceToNearestMeters(float $latitude, float $longitude, ?int $radiusMeters = null): ?float
    {
        $distance = static::query()
            ->withNearestDistance($latitude, $longitude, $radiusMeters)
            ->value('distance');

        if ($distance === null) {
            return null;
        }

        return (float) $distance;
    }
}

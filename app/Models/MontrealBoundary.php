<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MontrealBoundary extends Model
{
    use HasFactory;

    protected $table = 'montreal_boundary';

    protected $fillable = ['name'];

    /**
     * Check if a point (lat, lng) is inside the Montreal boundary.
     */
    public static function contains(float $latitude, float $longitude): bool
    {
        $result = DB::selectOne(
            'SELECT EXISTS (
                SELECT 1 FROM montreal_boundary
                WHERE ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))
            ) as inside',
            [$longitude, $latitude]
        );

        return (bool) $result->inside;
    }

    /**
     * Get the single boundary record (there should only be one).
     */
    public static function getBoundary(): ?self
    {
        return self::first();
    }
}

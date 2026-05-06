<?php

namespace Database\Seeders;

use App\Models\MontrealBoundary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MontrealBoundarySeeder extends Seeder
{
    public function run(): void
    {
        // Simplified polygon covering the Island of Montreal
        // Coordinates: [longitude, latitude]
        $coordinates = [
            [-73.95, 45.52],
            [-73.92, 45.60],
            [-73.85, 45.68],
            [-73.75, 45.70],
            [-73.65, 45.68],
            [-73.55, 45.65],
            [-73.48, 45.60],
            [-73.45, 45.52],
            [-73.48, 45.45],
            [-73.55, 45.40],
            [-73.65, 45.42],
            [-73.75, 45.43],
            [-73.85, 45.45],
            [-73.92, 45.48],
            [-73.95, 45.52], // close the polygon
        ];

        $polygonWkt = $this->toWktPolygon($coordinates);

        $boundary = MontrealBoundary::create(['name' => 'Island of Montreal']);

        DB::statement(
            'UPDATE montreal_boundary SET boundary = ST_GeomFromText(?, 4326) WHERE id = ?',
            [$polygonWkt, $boundary->id]
        );
    }

    /**
     * Convert coordinate array to WKT POLYGON string.
     *
     * @param  array<array{float, float}>  $coordinates
     */
    private function toWktPolygon(array $coordinates): string
    {
        $points = array_map(
            fn (array $coord) => implode(' ', $coord),
            $coordinates
        );

        return 'POLYGON(('.implode(',', $points).'))';
    }
}

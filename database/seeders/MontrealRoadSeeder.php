<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MontrealRoadSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('montreal_roads')->delete();

        $segments = [
            [
                'name' => 'Rue Sainte-Catherine O',
                'source' => 'osm-baseline',
                'wkt' => 'LINESTRING(-73.5750 45.5008, -73.5652 45.5017, -73.5565 45.5025)',
            ],
            [
                'name' => 'Boulevard Rene-Levesque O',
                'source' => 'osm-baseline',
                'wkt' => 'LINESTRING(-73.5780 45.4991, -73.5673 45.5000, -73.5580 45.5008)',
            ],
            [
                'name' => 'Avenue du Parc',
                'source' => 'osm-baseline',
                'wkt' => 'LINESTRING(-73.5945 45.5090, -73.5900 45.5135, -73.5855 45.5180)',
            ],
        ];

        foreach ($segments as $segment) {
            DB::statement(
                'INSERT INTO montreal_roads (name, source, geom, created_at, updated_at)
                 VALUES (?, ?, ST_GeomFromText(?, 4326), NOW(), NOW())',
                [$segment['name'], $segment['source'], $segment['wkt']]
            );
        }
    }
}

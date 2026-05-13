<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MontrealRoadSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('montreal_roads')->delete();

        $geojsonPath = database_path('geo/mtl_geobase.json');
        $geojson = json_decode(file_get_contents($geojsonPath), true);
        if (!isset($geojson['features'])) {
            throw new \RuntimeException('GeoJSON missing features array');
        }

        foreach ($geojson['features'] as $feature) {
            if (!isset($feature['geometry']['type']) || $feature['geometry']['type'] !== 'LineString') {
                continue;
            }
            $coords = $feature['geometry']['coordinates'];
            if (!is_array($coords) || count($coords) < 2) {
                continue;
            }
            // Build WKT string: "LINESTRING(lon lat, lon lat, ...)"
            $wktCoords = array_map(function($pt) {
                return $pt[0] . ' ' . $pt[1];
            }, $coords);
            $wkt = 'LINESTRING(' . implode(', ', $wktCoords) . ')';

            // Prefer ODONYME, fallback to NOM_VOIE, fallback to 'Unnamed'
            $props = $feature['properties'] ?? [];
            $name = trim($props['ODONYME'] ?? '') ?: trim($props['NOM_VOIE'] ?? '') ?: 'Unnamed';
            $borough = trim($props['ARR_GCH'] ?? '') ?: trim($props['ARR_DRT'] ?? '') ?: 'Montreal';

            DB::statement(
                'INSERT INTO montreal_roads (name, borough, source, geom, created_at, updated_at)
                 VALUES (?, ?, ?, ST_GeomFromText(?, 4326), NOW(), NOW())',
                [$name, $borough, 'mtl_geobase', $wkt]
            );
        }
    }
}

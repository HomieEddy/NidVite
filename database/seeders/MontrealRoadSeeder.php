<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MontrealRoadSeeder extends Seeder
{
    private const INSERT_CHUNK_SIZE = 500;

    public function run(): void
    {
        DB::statement('TRUNCATE TABLE montreal_roads RESTART IDENTITY');

        $geojsonPath = database_path('geo/mtl_geobase.json');
        $geojsonContents = file_get_contents($geojsonPath);
        if ($geojsonContents === false) {
            throw new RuntimeException("Unable to read GeoJSON file at path: {$geojsonPath}");
        }

        $geojson = json_decode($geojsonContents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode GeoJSON: '.json_last_error_msg());
        }

        if (! isset($geojson['features'])) {
            throw new RuntimeException('GeoJSON missing features array');
        }

        $rows = 0;
        $chunk = [];

        foreach ($geojson['features'] as $feature) {
            if (! isset($feature['geometry']['type']) || $feature['geometry']['type'] !== 'LineString') {
                continue;
            }
            $coords = $feature['geometry']['coordinates'];
            if (! is_array($coords) || count($coords) < 2) {
                continue;
            }
            // Build WKT string: "LINESTRING(lon lat, lon lat, ...)"
            $wktCoords = array_map(function ($pt) {
                return $pt[0].' '.$pt[1];
            }, $coords);
            $wkt = 'LINESTRING('.implode(', ', $wktCoords).')';

            // Prefer ODONYME, fallback to NOM_VOIE, fallback to 'Unnamed'
            $props = $feature['properties'] ?? [];
            $name = trim($props['ODONYME'] ?? '') ?: trim($props['NOM_VOIE'] ?? '') ?: 'Unnamed';
            $borough = trim($props['ARR_GCH'] ?? '') ?: trim($props['ARR_DRT'] ?? '') ?: 'Montreal';

            $chunk[] = [$name, $borough, 'mtl_geobase', $wkt];
            $rows++;

            if (count($chunk) >= self::INSERT_CHUNK_SIZE) {
                $this->insertChunk($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->insertChunk($chunk);
        }

        $this->command?->info("MontrealRoadSeeder imported {$rows} mtl_geobase road segments.");
    }

    /**
     * @param  array<int, array{string, string, string, string}>  $chunk
     */
    private function insertChunk(array $chunk): void
    {
        $placeholders = [];
        $bindings = [];

        foreach ($chunk as $row) {
            $placeholders[] = '(?, ?, ?, ST_GeomFromText(?, 4326), NOW(), NOW())';
            array_push($bindings, ...$row);
        }

        DB::statement(
            'INSERT INTO montreal_roads (name, borough, source, geom, created_at, updated_at) VALUES '
            .implode(', ', $placeholders),
            $bindings
        );
    }
}

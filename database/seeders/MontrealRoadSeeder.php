<?php

namespace Database\Seeders;

use Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MontrealRoadSeeder extends Seeder
{
    private const INSERT_CHUNK_SIZE = 100;

    public function run(): void
    {
        DB::statement('TRUNCATE TABLE montreal_roads RESTART IDENTITY');

        $geojsonPath = database_path('geo/mtl_geobase.json');
        if (! is_file($geojsonPath)) {
            throw new RuntimeException("Unable to read GeoJSON file at path: {$geojsonPath}");
        }

        $rows = 0;
        $chunk = [];

        foreach ($this->features($geojsonPath) as $featureJson) {
            $feature = json_decode($featureJson, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($feature)) {
                throw new RuntimeException('Failed to decode GeoJSON feature: '.json_last_error_msg());
            }

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
     * Streams the FeatureCollection without decoding the full 42 MB file at once.
     *
     * @return Generator<int, string>
     */
    private function features(string $geojsonPath): Generator
    {
        $handle = fopen($geojsonPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to read GeoJSON file at path: {$geojsonPath}");
        }

        try {
            $buffer = '';
            $inFeatures = false;
            $collecting = false;
            $feature = '';
            $depth = 0;
            $inString = false;
            $escaped = false;

            while (($chunk = fread($handle, 65536)) !== false && $chunk !== '') {
                $length = strlen($chunk);

                for ($i = 0; $i < $length; $i++) {
                    $char = $chunk[$i];

                    if (! $inFeatures) {
                        $buffer .= $char;
                        if (str_contains($buffer, '"features"')) {
                            $afterFeatures = substr($buffer, (int) strpos($buffer, '"features"') + 10);
                            if (str_contains($afterFeatures, '[')) {
                                $inFeatures = true;
                            }
                        }

                        if (strlen($buffer) > 1024) {
                            $buffer = substr($buffer, -1024);
                        }

                        continue;
                    }

                    if (! $collecting) {
                        if ($char === '{') {
                            $collecting = true;
                            $feature = '{';
                            $depth = 1;
                            $inString = false;
                            $escaped = false;
                        } elseif ($char === ']') {
                            return;
                        }

                        continue;
                    }

                    $feature .= $char;

                    if ($escaped) {
                        $escaped = false;

                        continue;
                    }

                    if ($char === '\\' && $inString) {
                        $escaped = true;

                        continue;
                    }

                    if ($char === '"') {
                        $inString = ! $inString;

                        continue;
                    }

                    if ($inString) {
                        continue;
                    }

                    if ($char === '{') {
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;

                        if ($depth === 0) {
                            yield $feature;
                            $collecting = false;
                            $feature = '';
                        }
                    }
                }
            }
        } finally {
            fclose($handle);
        }
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

        $this->command?->getOutput()->write('.');
    }
}

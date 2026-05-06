<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Show the public map page.
     */
    public function index(Request $request): View
    {
        return view('map', [
            'embedded' => $request->boolean('embed'),
        ]);
    }

    /**
     * Return reports as GeoJSON for map display.
     */
    public function geojson(): JsonResponse
    {
        $reports = Report::where('is_spam', false)
            ->whereNotNull('location')
            ->select([
                'id',
                'uuid',
                'status',
                'description',
                'address',
                'neighborhood',
                'borough',
                'category_id',
            ])
            ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $reports->map(function (Report $report): array {
                /** @phpstan-ignore property.notFound */
                $longitude = (float) $report->longitude;
                /** @phpstan-ignore property.notFound */
                $latitude = (float) $report->latitude;

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$longitude, $latitude],
                    ],
                    'properties' => [
                        'uuid' => $report->uuid,
                        'status' => $report->status,
                        'status_label' => __("tracking.status.{$report->status}"),
                        'description' => $report->description,
                        'address' => $report->address,
                        'neighborhood' => $report->neighborhood,
                        'borough' => $report->borough,
                        'url' => route('report.tracking', $report->uuid),
                    ],
                ];
            }),
        ]);
    }
}

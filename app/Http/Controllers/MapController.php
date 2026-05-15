<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Show the public map page.
     */
    public function index(Request $request): View
    {
        if ($request->boolean('embed')) {
            return view('map-embed');
        }

        return view('map', [
            'embedded' => $request->boolean('embed'),
        ]);
    }

    /**
     * Return reports as GeoJSON for map display.
     */
    public function geojson(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $validStatuses = array_values(array_filter(
            ReportStatus::values(),
            fn (string $value): bool => $value !== ReportStatus::Rejected->value
        ));
        $status = is_string($status) && in_array($status, $validStatuses, true) ? $status : null;

        $reportsQuery = Report::where('is_spam', false)
            ->where('status', '!=', ReportStatus::Rejected->value)
            ->whereNotNull('location')
            ->select([
                'id',
                'public_tracking_id',
                'status',
                'description',
                'address',
                'neighborhood',
                'borough',
                'category_id',
            ])
            ->withCoordinates();

        if ($status !== null) {
            $reportsQuery->where('status', $status);
        }

        $reports = $reportsQuery->get();

        $features = [];

        foreach ($reports as $report) {
            $coordinates = $report->coordinates();

            if ($coordinates === null) {
                Log::warning('Skipping report without coordinate payload in geojson response.', [
                    'report_id' => $report->getKey(),
                ]);

                continue;
            }

            $longitude = (float) $coordinates['lng'];
            $latitude = (float) $coordinates['lat'];

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$longitude, $latitude],
                ],
                'properties' => [
                    'tracking_id' => $report->public_tracking_id,
                    'status' => $report->status,
                    'status_label' => __("tracking.status.{$report->status}"),
                    'description' => $report->description,
                    'address' => $report->address,
                    'neighborhood' => $report->neighborhood,
                    'borough' => $report->borough,
                    'url' => route('report.tracking', $report->public_tracking_id),
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}

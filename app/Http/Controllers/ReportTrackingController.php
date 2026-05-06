<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReportTrackingController extends Controller
{
    public function show(string $uuid): View
    {
        $report = Report::where('uuid', $uuid)
            ->with('category')
            ->firstOrFail();

        $location = null;
        /** @phpstan-ignore property.notFound */
        if ($report->location !== null) {
            $location = \DB::selectOne(
                'SELECT ST_Y(location::geometry) as lat, ST_X(location::geometry) as lng FROM reports WHERE id = ?',
                [$report->id]
            );
        }

        return view('tracking', compact('report', 'location'));
    }

    public function lookup(string $uuid): JsonResponse
    {
        $report = Report::where('uuid', $uuid)
            ->with('category')
            ->first();

        if (! $report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $location = null;
        /** @phpstan-ignore property.notFound */
        if ($report->location !== null) {
            $location = \DB::selectOne(
                'SELECT ST_Y(location::geometry) as lat, ST_X(location::geometry) as lng FROM reports WHERE id = ?',
                [$report->id]
            );
        }

        $steps = ['received', 'verified', 'scheduled', 'in_progress', 'repaired'];
        $currentIndex = array_search($report->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        return response()->json([
            'uuid' => $report->uuid,
            'status' => $report->status,
            'status_label' => __("report.status.{$report->status}"),
            'address' => $report->address,
            'description' => $report->description,
            'category' => $report->category ? (app()->getLocale() === 'fr' ? $report->category->label_fr : $report->category->label_en) : null,
            'created_at' => $report->created_at->toIso8601String(),
            'rejection_reason' => $report->rejection_reason,
            'location' => $location ? ['lat' => (float) $location->lat, 'lng' => (float) $location->lng] : null,
            'progress' => [
                'current_step' => $currentIndex,
                'total_steps' => count($steps),
                'percent' => $report->status === 'rejected' ? 0 : ($currentIndex >= 0 ? (($currentIndex + 1) / count($steps)) * 100 : 0),
            ],
            'steps' => array_map(fn ($step, $idx) => [
                'status' => $step,
                'label' => __("report.status.{$step}"),
                'done' => $idx <= $currentIndex,
                'current' => $idx === $currentIndex,
            ], $steps, array_keys($steps)),
        ]);
    }
}

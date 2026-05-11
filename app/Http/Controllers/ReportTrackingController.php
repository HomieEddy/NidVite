<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReportTrackingController extends Controller
{
    public function show(string $trackingId): View
    {
        $report = Report::where('public_tracking_id', $trackingId)
            ->with(['category', 'media'])
            ->firstOrFail();

        $location = $report->coordinatePoint();

        $photoUrls = $report->signedPhotoUrls();

        return view('tracking', compact('report', 'location', 'photoUrls'));
    }

    public function lookup(string $trackingId): JsonResponse
    {
        $report = Report::where('public_tracking_id', $trackingId)
            ->with(['category', 'media'])
            ->first();

        if (! $report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $location = $report->coordinates();

        $steps = ['received', 'verified', 'scheduled', 'in_progress', 'repaired'];
        $currentIndex = array_search($report->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        return response()->json([
            'tracking_id' => $report->public_tracking_id,
            'status' => $report->status,
            'status_label' => __("report.status.{$report->status}"),
            'address' => $report->address,
            'description' => $report->description,
            'category' => $report->category ? (app()->getLocale() === 'fr' ? $report->category->label_fr : $report->category->label_en) : null,
            'created_at' => $report->created_at->toIso8601String(),
            'rejection_reason' => $report->rejection_reason,
            'photos' => $report->signedPhotoUrls(),
            'location' => $location,
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

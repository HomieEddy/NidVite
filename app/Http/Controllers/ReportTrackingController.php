<?php

namespace App\Http\Controllers;

use App\Models\Report;
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
}

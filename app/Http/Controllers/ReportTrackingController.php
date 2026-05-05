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

        return view('tracking', compact('report'));
    }
}

<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportTrackingController;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $totalReported = Report::count();
    $totalFixed = Report::where('status', 'repaired')->count();
    $totalPending = Report::whereIn('status', ['received', 'verified', 'scheduled', 'in_progress'])->count();

    $avgDays = Report::where('status', 'repaired')
        ->whereNotNull('completed_at')
        ->whereNotNull('created_at')
        ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 86400) as avg_days')
        ->value('avg_days');

    if (app()->getLocale() === 'fr') {
        $velocity = $avgDays ? round($avgDays, 1) . ' jours' : 'N/D';
    } else {
        $velocity = $avgDays ? round($avgDays, 1) . ' days' : 'N/A';
    }

    return view('welcome', compact('totalReported', 'totalFixed', 'totalPending', 'velocity'));
});

Route::get('/signaler', function () {
    return view('report');
})->name('report.create');

Route::get('/suivi/{uuid}', [ReportTrackingController::class, 'show'])
    ->name('report.tracking')
    ->whereUuid('uuid');

Route::get('/carte', [MapController::class, 'index'])
    ->name('map.public');

Route::get('/api/reports/geojson', [MapController::class, 'geojson'])
    ->name('api.reports.geojson');

Route::get('/api/reports/{uuid}/lookup', [ReportTrackingController::class, 'lookup'])
    ->name('api.reports.lookup')
    ->whereUuid('uuid');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['fr', 'en'], true)) {
        session()->put('locale', $locale);
        app()->setLocale($locale);
    }

    return redirect()->back();
})->name('locale.switch');

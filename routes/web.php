<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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

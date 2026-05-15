<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportTrackingController;
use App\Http\Controllers\SignedMediaController;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\ResponseCache\Middlewares\CacheResponse;

$publicApiThrottle = sprintf('%d,1', max(1, (int) config('tracking_experience.public_api_rate_limit_per_minute', 60)));

Route::get('/', [HomeController::class, 'index'])->when(! app()->environment('staging'), function ($route) {
    $route->middleware(CacheResponse::class);
});

Route::get('/signaler', function () {
    return view('report');
})->name('report.create');

Route::get('/suivi/{trackingId}', [ReportTrackingController::class, 'show'])
    ->name('report.tracking')
    ->where('trackingId', 'MTL[A-Z0-9]{8}');

Route::post('/suivi/{trackingId}/preferences', [ReportTrackingController::class, 'updatePreference'])
    ->name('report.tracking.preference.update')
    ->where('trackingId', 'MTL[A-Z0-9]{8}')
    ->middleware('throttle:20,1');

Route::post('/suivi/{trackingId}/follow', [ReportTrackingController::class, 'follow'])
    ->name('report.followers.store')
    ->where('trackingId', 'MTL[A-Z0-9]{8}')
    ->middleware('throttle:20,1');

Route::get('/suivi/{trackingId}/unsubscribe/{follower}', [ReportTrackingController::class, 'unsubscribe'])
    ->name('report.followers.unsubscribe')
    ->where('trackingId', 'MTL[A-Z0-9]{8}')
    ->middleware(['signed', 'throttle:20,1']);

Route::view('/confidentialite', 'pages.privacy')
    ->name('legal.privacy');

Route::view('/conditions', 'pages.terms')
    ->name('legal.terms');

Route::get('/carte', [MapController::class, 'index'])
    ->name('map.public')
    ->when(! app()->environment('staging'), function ($route) {
        $route->middleware(CacheResponse::class);
    });

Route::get('/api/reports/geojson', [MapController::class, 'geojson'])
    ->name('api.reports.geojson')
    ->middleware('throttle:'.$publicApiThrottle);

Route::get('/api/reports/{trackingId}/lookup', [ReportTrackingController::class, 'lookup'])
    ->name('api.reports.lookup')
    ->where('trackingId', 'MTL[A-Z0-9]{8}')
    ->middleware('throttle:'.$publicApiThrottle);

Route::get('/api/reports/duplicate-hint', [ReportTrackingController::class, 'duplicateHint'])
    ->name('api.reports.duplicate-hint')
    ->middleware('throttle:'.$publicApiThrottle);

Route::get('/health', HealthCheckJsonResultsController::class)
    ->name('health.json');

Route::get('/media/{media}', SignedMediaController::class)
    ->name('media.signed')
    ->middleware(['signed', 'throttle:'.$publicApiThrottle]);

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['fr', 'en'], true)) {
        app()->setLocale($locale);

        Cookie::queue(cookie(
            name: 'locale',
            value: $locale,
            minutes: 60 * 24 * 365,
            path: '/',
            secure: (bool) config('session.secure'),
            httpOnly: true,
            sameSite: (string) config('session.same_site', 'lax'),
        ));

        return redirect()->back();
    }

    return redirect()->back();
})->name('locale.switch');

<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportTrackingController;
use App\Http\Controllers\SignedMediaController;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\ResponseCache\Middlewares\CacheResponse;

$publicApiThrottle = 'throttle:'.sprintf('%d,1', max(1, (int) config('tracking_experience.public_api_rate_limit_per_minute', 60)));
$trackingMutationThrottle = 'throttle:20,1';
$cacheWhenPublic = static fn ($route) => $route->when(! app()->environment('staging'), function ($route) {
    $route->middleware(CacheResponse::class);
});

Route::pattern('trackingId', 'MTL[A-Z0-9]{8}');

$cacheWhenPublic(Route::get('/', [HomeController::class, 'index']));

Route::view('/signaler', 'report')
    ->name('report.create');

Route::view('/offline', 'vendor.laravelpwa.offline')
    ->name('offline');

Route::prefix('/suivi/{trackingId}')
    ->controller(ReportTrackingController::class)
    ->group(function () use ($publicApiThrottle, $trackingMutationThrottle): void {
        Route::get('/', 'show')
            ->name('report.tracking')
            ->middleware($publicApiThrottle);

        Route::post('/preferences', 'updatePreference')
            ->name('report.tracking.preference.update')
            ->middleware($trackingMutationThrottle);

        Route::post('/follow', 'follow')
            ->name('report.followers.store')
            ->middleware($trackingMutationThrottle);

        Route::get('/unsubscribe/{follower}', 'unsubscribe')
            ->name('report.followers.unsubscribe')
            ->middleware(['signed', $trackingMutationThrottle]);
    });

Route::view('/confidentialite', 'pages.privacy')
    ->name('legal.privacy');

Route::view('/conditions', 'pages.terms')
    ->name('legal.terms');

$cacheWhenPublic(
    Route::get('/carte', [MapController::class, 'index'])
        ->name('map.public')
);

Route::prefix('/api/reports')
    ->name('api.reports.')
    ->middleware($publicApiThrottle)
    ->group(function (): void {
        Route::get('/geojson', [MapController::class, 'geojson'])
            ->name('geojson');

        Route::get('/{trackingId}/lookup', [ReportTrackingController::class, 'lookup'])
            ->name('lookup');

        Route::get('/duplicate-hint', [ReportTrackingController::class, 'duplicateHint'])
            ->name('duplicate-hint');
    });

Route::get('/health', HealthCheckJsonResultsController::class)
    ->name('health.json');

Route::get('/media/{media}', SignedMediaController::class)
    ->name('media.signed')
    ->middleware(['signed', $publicApiThrottle]);

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
})->name('locale.switch')
    ->middleware($publicApiThrottle);

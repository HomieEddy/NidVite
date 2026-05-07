<?php

use App\Http\Middleware\GenerateDeviceFingerprint;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottleReportSubmission;
use Bepsvpt\SecureHeaders\SecureHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecureHeadersMiddleware::class);

        $middleware->web([
            SetLocale::class,
            GenerateDeviceFingerprint::class,
            ThrottleReportSubmission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();

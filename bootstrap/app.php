<?php

use App\Http\Middleware\GenerateDeviceFingerprint;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\RemovePermissionsPolicyHeader;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottleReportSubmission;
use Bepsvpt\SecureHeaders\SecureHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cookie\Middleware\EncryptCookies as FrameworkEncryptCookies;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->append(SecureHeadersMiddleware::class);
        $middleware->append(RemovePermissionsPolicyHeader::class);

        $middleware->web(replace: [
            FrameworkEncryptCookies::class => EncryptCookies::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            GenerateDeviceFingerprint::class,
            ThrottleReportSubmission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();

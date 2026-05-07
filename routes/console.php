<?php

use Bepsvpt\SecureHeaders\SecureHeaders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('security:check-headers', function () {
    $issues = [];

    $headers = (new SecureHeaders(config('secure-headers', [])))->headers();

    $requiredHeaders = [
        'Content-Security-Policy',
        'Strict-Transport-Security',
        'X-Frame-Options',
        'X-Content-Type-Options',
    ];

    foreach ($requiredHeaders as $header) {
        if (! isset($headers[$header]) || $headers[$header] === '') {
            $issues[] = "Missing {$header} header.";
        }
    }

    if (config('secure-headers.csp.enable') !== true) {
        $issues[] = 'secure-headers.csp.enable must be true.';
    }

    if (config('secure-headers.hsts.enable') !== true) {
        $issues[] = 'secure-headers.hsts.enable must be true.';
    }

    if (config('secure-headers.x-content-type-options') !== 'nosniff') {
        $issues[] = 'secure-headers.x-content-type-options must be nosniff.';
    }

    $frameOptions = strtolower((string) config('secure-headers.x-frame-options'));

    if (! in_array($frameOptions, ['deny', 'sameorigin'], true)) {
        $issues[] = 'secure-headers.x-frame-options must be deny or sameorigin.';
    }

    if ($issues !== []) {
        foreach ($issues as $issue) {
            $this->error($issue);
        }

        return 1;
    }

    $this->info('Secure headers configuration check passed.');

    return 0;
})->purpose('Fail-fast validation for secure headers configuration in CI');

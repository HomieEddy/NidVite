<?php

function sentryConfigFromEnvironment(array $env): array
{
    $keys = ['SENTRY_LARAVEL_DSN', 'SENTRY_DSN', 'SENTRY_RELEASE', 'APP_VERSION', 'SENTRY_ENVIRONMENT', 'APP_ENV', 'SENTRY_TRACES_SAMPLE_RATE'];
    $original = [];

    foreach ($keys as $key) {
        $value = getenv($key);
        $original[$key] = $value === false ? null : $value;
        putenv("{$key}");
        unset($_ENV[$key], $_SERVER[$key]);
    }

    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
        $_ENV[$key] = (string) $value;
        $_SERVER[$key] = (string) $value;
    }

    /** @var array<string, mixed> $config */
    $config = require base_path('config/sentry.php');

    foreach ($keys as $key) {
        if ($original[$key] === null) {
            putenv("{$key}");
            unset($_ENV[$key], $_SERVER[$key]);

            continue;
        }

        putenv("{$key}={$original[$key]}");
        $_ENV[$key] = (string) $original[$key];
        $_SERVER[$key] = (string) $original[$key];
    }

    return $config;
}

it('prefers SENTRY_LARAVEL_DSN over fallback SENTRY_DSN', function () {
    $config = sentryConfigFromEnvironment([
        'SENTRY_LARAVEL_DSN' => 'https://laravel-dsn@example.com/1',
        'SENTRY_DSN' => 'https://fallback-dsn@example.com/2',
    ]);

    expect($config['dsn'])->toBe('https://laravel-dsn@example.com/1');
});

it('falls back to SENTRY_DSN when SENTRY_LARAVEL_DSN is absent', function () {
    $config = sentryConfigFromEnvironment([
        'SENTRY_DSN' => 'https://fallback-dsn@example.com/2',
    ]);

    expect($config['dsn'])->toBe('https://fallback-dsn@example.com/2');
});

it('resolves release/environment fallbacks and traces sample rate casting', function () {
    $config = sentryConfigFromEnvironment([
        'APP_VERSION' => 'release-2026.05.07',
        'APP_ENV' => 'testing-env',
        'SENTRY_TRACES_SAMPLE_RATE' => '0.35',
    ]);

    expect($config['release'])->toBe('release-2026.05.07')
        ->and($config['environment'])->toBe('testing-env')
        ->and($config['traces_sample_rate'])->toBe(0.35);
});

it('keeps traces sample rate null when env is not provided', function () {
    $config = sentryConfigFromEnvironment([
        'APP_ENV' => 'production',
    ]);

    expect($config['traces_sample_rate'])->toBeNull();
});

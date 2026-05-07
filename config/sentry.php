<?php

use Sentry\Event;

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE', env('APP_VERSION')),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    'before_send' => static function (Event $event): Event {
        $environment = (string) config('sentry.environment', config('app.env'));
        $release = (string) config('sentry.release', 'unknown');

        $event->setTag('environment', $environment);
        $event->setTag('release', $release !== '' ? $release : 'unknown');

        return $event;
    },

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null
        ? null
        : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
];

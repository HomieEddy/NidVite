<?php

it('loads sentry dsn from environment configuration', function () {
    config()->set('sentry.dsn', 'https://public@example.com/1');

    expect(config('sentry.dsn'))->toBe('https://public@example.com/1');
});

it('exposes sentry environment and release configuration keys', function () {
    config()->set('sentry.environment', 'testing-env');
    config()->set('sentry.release', 'release-2026.05.07');

    expect(config('sentry.environment'))->toBe('testing-env');
    expect(config('sentry.release'))->toBe('release-2026.05.07');
});

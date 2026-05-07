<?php

use Sentry\Event;

it('loads sentry dsn from environment configuration', function () {
    config()->set('sentry.dsn', 'https://public@example.com/1');

    expect(config('sentry.dsn'))->toBe('https://public@example.com/1');
});

it('adds environment and release tags before sending events', function () {
    config()->set('sentry.environment', 'testing-env');
    config()->set('sentry.release', 'release-2026.05.07');

    $beforeSend = config('sentry.before_send');

    expect($beforeSend)->toBeCallable();

    $event = Event::createEvent();
    $processed = $beforeSend($event);

    expect($processed->getTags()['environment'] ?? null)->toBe('testing-env');
    expect($processed->getTags()['release'] ?? null)->toBe('release-2026.05.07');
});

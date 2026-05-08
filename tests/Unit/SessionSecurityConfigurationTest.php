<?php

use Tests\TestCase;

uses(TestCase::class);

it('uses hardened session defaults at runtime', function () {
    config([
        'session.encrypt' => true,
        'session.secure' => true,
        'session.http_only' => true,
        'session.same_site' => 'lax',
    ]);

    expect(config('session.encrypt'))->toBeTrue();
    expect(config('session.secure'))->toBeTrue();
    expect(config('session.http_only'))->toBeTrue();
    expect(config('session.same_site'))->toBe('lax');

    config([
        'session.encrypt' => false,
        'session.secure' => false,
    ]);

    expect(config('session.encrypt'))->toBeFalse();
    expect(config('session.secure'))->toBeFalse();
});

it('keeps secure session defaults documented in env example', function () {
    $envExample = file_get_contents(__DIR__.'/../../.env.example');

    $pairs = collect(preg_split('/\r\n|\r|\n/', $envExample))
        ->filter(fn ($line) => trim($line) !== '' && ! str_starts_with(trim($line), '#') && str_contains($line, '='))
        ->mapWithKeys(function ($line) {
            [$key, $value] = explode('=', $line, 2);

            return [trim($key) => trim($value)];
        });

    expect($pairs->get('SESSION_ENCRYPT'))->toBe('true');
    expect($pairs->get('SESSION_SECURE_COOKIE'))->toBe('false');
    expect($pairs->get('SESSION_HTTP_ONLY'))->toBe('true');
    expect($pairs->get('SESSION_SAME_SITE'))->toBe('lax');

    $mutatedEnv = str_replace('SESSION_ENCRYPT=true', 'SESSION_ENCRYPT=false', $envExample);
    $mutatedPairs = collect(preg_split('/\r\n|\r|\n/', $mutatedEnv))
        ->filter(fn ($line) => trim($line) !== '' && ! str_starts_with(trim($line), '#') && str_contains($line, '='))
        ->mapWithKeys(function ($line) {
            [$key, $value] = explode('=', $line, 2);

            return [trim($key) => trim($value)];
        });

    expect($mutatedPairs->get('SESSION_ENCRYPT'))->toBe('false');
});

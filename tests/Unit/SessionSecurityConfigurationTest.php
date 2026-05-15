<?php

use Tests\TestCase;

uses(TestCase::class);

it('keeps hardened security defaults in session config source', function () {
    $sessionConfig = file_get_contents(base_path('config/session.php'));

    expect($sessionConfig)->toContain("'encrypt' => env('SESSION_ENCRYPT', true)");
    expect($sessionConfig)->toContain("'secure' => env('SESSION_SECURE_COOKIE', true)");
    expect($sessionConfig)->toContain("'http_only' => env('SESSION_HTTP_ONLY', true)");
    expect($sessionConfig)->toContain("'same_site' => env('SESSION_SAME_SITE', 'lax')");
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

    expect($envExample)->toContain('Keep false for local HTTP');
});

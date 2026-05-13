<?php

use Illuminate\Support\Facades\Config;

it('adds required secure headers to responses', function () {
    $response = $this->get('/up');

    $response->assertOk();
    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('Strict-Transport-Security');
    $response->assertHeader('X-Frame-Options', 'sameorigin');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Permissions-Policy');

    expect((string) $response->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=');

    expect((string) $response->headers->get('Permissions-Policy'))
        ->toContain('geolocation=(self)');
});

it('passes the secure headers ci guard command when configuration is valid', function () {
    $this->artisan('security:check-headers')->assertSuccessful();
});

it('fails the secure headers ci guard command when configuration is invalid', function () {
    Config::set('secure-headers.hsts.enable', false);

    $this->artisan('security:check-headers')->assertExitCode(1);
});

it('fails the secure headers ci guard command when x-frame-options is invalid', function () {
    Config::set('secure-headers.x-frame-options', 'allowall');

    $this->artisan('security:check-headers')->assertExitCode(1);
});

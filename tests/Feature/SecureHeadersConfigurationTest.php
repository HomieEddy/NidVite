<?php

use Illuminate\Support\Facades\Config;

function setReverbCspSources(?string $host, ?string $port): void
{
    $sources = [
        'https://www.google.com/recaptcha/',
        'https://www.gstatic.com/recaptcha/',
        'https://www.recaptcha.net/recaptcha/',
        'https://nominatim.openstreetmap.org',
    ];

    if ($host !== null && $port !== null) {
        $sources[] = sprintf('ws://%s:%s', $host, $port);
        $sources[] = sprintf('wss://%s:%s', $host, $port);
    }

    Config::set('secure-headers.csp.connect-src.allow', $sources);
}

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

    expect((string) $response->headers->get('Permissions-Policy'))
        ->not->toContain('geolocation=*');
});

it('adds websocket csp sources when reverb host and port are configured', function () {
    $reverbHost = 'reverb.test';
    $reverbPort = '8080';
    setReverbCspSources($reverbHost, $reverbPort);

    $response = $this->get('/up');

    $response->assertOk();

    expect((string) $response->headers->get('Content-Security-Policy'))
        ->toContain(sprintf('ws://%s:%s', $reverbHost, $reverbPort))
        ->toContain(sprintf('wss://%s:%s', $reverbHost, $reverbPort));
});

it('omits websocket csp sources when reverb host is missing', function () {
    $reverbHost = 'reverb.test';
    $reverbPort = '8080';
    setReverbCspSources(null, $reverbPort);

    $response = $this->get('/up');

    $response->assertOk();

    expect((string) $response->headers->get('Content-Security-Policy'))
        ->not->toContain(sprintf('ws://%s:%s', $reverbHost, $reverbPort))
        ->not->toContain(sprintf('wss://%s:%s', $reverbHost, $reverbPort));
});

it('omits websocket csp sources when reverb port is missing', function () {
    $reverbHost = 'reverb.test';
    $reverbPort = '8080';
    setReverbCspSources($reverbHost, null);

    $response = $this->get('/up');

    $response->assertOk();

    expect((string) $response->headers->get('Content-Security-Policy'))
        ->not->toContain(sprintf('ws://%s:%s', $reverbHost, $reverbPort))
        ->not->toContain(sprintf('wss://%s:%s', $reverbHost, $reverbPort));
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

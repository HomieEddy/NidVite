<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('locks out login attempts after five failures for fifteen minutes', function () {
    $email = 'admin@example.com';
    $throttleKey = Str::transliterate(Str::lower($email.'|127.0.0.1'));
    RateLimiter::clear($throttleKey);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($throttleKey, 15 * 60);
    }

    expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeTrue();
    expect(RateLimiter::availableIn($throttleKey))->toBeGreaterThan(0);

    $this->travel(15)->minutes();
    expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeFalse();
});

it('registers a login limiter at five attempts per fifteen minutes', function () {
    $request = Request::create('/admin/login', 'POST', [
        'email' => 'admin@example.com',
    ]);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $limit = RateLimiter::limiter('login')($request);

    expect($limit->maxAttempts)->toBe(5);
    expect($limit->decaySeconds)->toBe(15 * 60);
});

it('returns localized lockout feedback in french', function () {
    app()->setLocale('fr');
    expect(__('auth.lockout', ['minutes' => 15]))->toContain('Trop de tentatives de connexion');
});

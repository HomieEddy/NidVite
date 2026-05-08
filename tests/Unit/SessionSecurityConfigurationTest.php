<?php

it('uses hardened session defaults in session config source', function () {
    $sessionConfig = file_get_contents(__DIR__.'/../../config/session.php');

    expect($sessionConfig)->toContain("'encrypt' => env('SESSION_ENCRYPT', true)");
    expect($sessionConfig)->toContain("'secure' => env('SESSION_SECURE_COOKIE', true)");
    expect($sessionConfig)->toContain("'http_only' => env('SESSION_HTTP_ONLY', true)");
    expect($sessionConfig)->toContain("'same_site' => env('SESSION_SAME_SITE', 'lax')");
});

it('keeps secure session defaults documented in env example', function () {
    $envExample = file_get_contents(__DIR__.'/../../.env.example');

    expect($envExample)->toContain('SESSION_ENCRYPT=true');
    expect($envExample)->toContain('SESSION_SECURE_COOKIE=true');
    expect($envExample)->toContain('SESSION_HTTP_ONLY=true');
    expect($envExample)->toContain('SESSION_SAME_SITE=lax');
});

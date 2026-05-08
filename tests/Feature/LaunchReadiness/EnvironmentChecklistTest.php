<?php

it('requires launch-critical environment variables to be declared in env example', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    expect($envExample)->toContain('RESEND_API_KEY=');
    expect($envExample)->toContain('NOCAPTCHA_SECRET=');
    expect($envExample)->toContain('R2_ACCESS_KEY_ID=');
    expect($envExample)->toContain('R2_BUCKET=');
    expect($envExample)->toContain('SENTRY_LARAVEL_DSN=');
    expect($envExample)->toContain('QUEUE_CONNECTION=redis');
    expect($envExample)->toContain('FILESYSTEM_DISK=local');
});

<?php

return [
    'session_timeout_minutes' => (int) env('ADMIN_SESSION_TIMEOUT_MINUTES', 15),
    'max_concurrent_sessions' => (int) env('ADMIN_MAX_CONCURRENT_SESSIONS', 2),
    'staging_demo_seed_password' => env('STAGING_DEMO_SEED_PASSWORD'),
];

<?php

return [
    'session_timeout_minutes' => (int) env('ADMIN_SESSION_TIMEOUT_MINUTES', 15),
    'max_concurrent_sessions' => (int) env('ADMIN_MAX_CONCURRENT_SESSIONS', 2),
];

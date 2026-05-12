<?php

return [
    'recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('OPS_WEEKLY_DIGEST_RECIPIENTS', ''))))),
    'locale' => env('OPS_WEEKLY_DIGEST_LOCALE', 'fr'),
    'day_of_week' => (int) env('OPS_WEEKLY_DIGEST_DAY_OF_WEEK', 1),
    'time' => env('OPS_WEEKLY_DIGEST_TIME', '08:00'),
    'window_days' => (int) env('OPS_WEEKLY_DIGEST_WINDOW_DAYS', 7),
    'hotspot_limit' => (int) env('OPS_WEEKLY_DIGEST_HOTSPOT_LIMIT', 5),
];

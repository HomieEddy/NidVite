<?php

return [
    'rapid_repeat' => [
        'window_minutes' => (int) env('SUSPICIOUS_RAPID_REPEAT_WINDOW_MINUTES', 5),
        'threshold' => (int) env('SUSPICIOUS_RAPID_REPEAT_THRESHOLD', 3),
    ],

    'geolocation' => [
        'window_minutes' => (int) env('SUSPICIOUS_GEO_WINDOW_MINUTES', 30),
        'max_travel_minutes' => (int) env('SUSPICIOUS_GEO_MAX_TRAVEL_MINUTES', 10),
        'min_distance_meters' => (int) env('SUSPICIOUS_GEO_MIN_DISTANCE_METERS', 5000),
    ],
];

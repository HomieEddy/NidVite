<?php

return [
    'public_api_rate_limit_per_minute' => max(1, (int) env('TRACKING_PUBLIC_API_RATE_LIMIT_PER_MINUTE', 60)),

    'eta' => [
        'status_days' => [
            'received' => ['min' => 2, 'max' => 4],
            'verified' => ['min' => 3, 'max' => 6],
            'scheduled' => ['min' => 1, 'max' => 3],
            'in_progress' => ['min' => 1, 'max' => 2],
            'repaired' => ['min' => 0, 'max' => 0],
        ],
        'zone_boroughs' => [
            'central' => [
                'Ville-Marie',
                'Le Plateau-Mont-Royal',
                'Le Sud-Ouest',
                'Mercier-Hochelaga-Maisonneuve',
                'Rosemont-La Petite-Patrie',
            ],
        ],
        'zone_multipliers' => [
            'central' => 1.0,
            'default' => 1.2,
        ],
    ],

    'duplicate_nudge' => [
        'radius_meters' => 50,
        'window_days' => 30,
        'open_statuses' => ['received', 'verified', 'scheduled', 'in_progress'],
    ],

    'followers' => [
        'retention_days' => (int) env('TRACKING_FOLLOWER_RETENTION_DAYS', 365),
    ],

    'qr' => [
        'size' => (int) env('TRACKING_QR_SIZE', 168),
    ],

    'evidence' => [
        'gps_warning_accuracy_meters' => 50,
        'photo' => [
            'dark_warning_threshold' => 45,
            'dark_severe_threshold' => 25,
            'blur_warning_threshold' => 12,
            'blur_severe_threshold' => 6,
        ],
    ],
];

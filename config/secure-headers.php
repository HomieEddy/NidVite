<?php

$reverbHost = env('VITE_REVERB_HOST', env('REVERB_HOST'));
$reverbPort = env('VITE_REVERB_PORT', env('REVERB_PORT'));
$reverbConnectSources = $reverbHost && $reverbPort
    ? [
        sprintf('ws://%s:%s', $reverbHost, $reverbPort),
        sprintf('wss://%s:%s', $reverbHost, $reverbPort),
    ]
    : [];

return [
    'server' => '',

    'x-content-type-options' => 'nosniff',

    'x-dns-prefetch-control' => 'off',

    'x-download-options' => 'noopen',

    'x-frame-options' => 'sameorigin',

    'x-permitted-cross-domain-policies' => 'none',

    'x-powered-by' => '',

    'x-xss-protection' => '',

    'referrer-policy' => 'strict-origin-when-cross-origin',

    'permissions-policy' => [
        'enable' => true,
        'camera' => [
            'none' => true,
            '*' => false,
            'self' => false,
            'origins' => [],
        ],
        'microphone' => [
            'none' => true,
            '*' => false,
            'self' => false,
            'origins' => [],
        ],
        'geolocation' => [
            'none' => false,
            '*' => false,
            'self' => true,
            'origins' => [],
        ],
    ],

    'hsts' => [
        'enable' => (bool) env('SECURE_HEADERS_HSTS_ENABLE', true),
        'max-age' => (int) env('SECURE_HEADERS_HSTS_MAX_AGE', 31536000),
        'include-sub-domains' => (bool) env('SECURE_HEADERS_HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => (bool) env('SECURE_HEADERS_HSTS_PRELOAD', false),
    ],

    'csp' => [
        'enable' => true,
        'report-only' => false,

        'default-src' => [
            'self' => true,
        ],

        'script-src' => [
            'self' => true,
            'unsafe-inline' => (bool) env('SECURE_HEADERS_CSP_UNSAFE_INLINE', false),
            'unsafe-eval' => (bool) env('SECURE_HEADERS_CSP_UNSAFE_EVAL', false),
            'allow' => [
                'https://www.google.com/recaptcha/',
                'https://www.gstatic.com/recaptcha/',
                'https://www.recaptcha.net/recaptcha/',
            ],
        ],

        'style-src' => [
            'self' => true,
            'unsafe-inline' => true,
            'allow' => [
                'https://fonts.bunny.net',
                'https://fonts.googleapis.com',
            ],
        ],

        'img-src' => [
            'self' => true,
            'schemes' => ['data', 'https'],
        ],

        'font-src' => [
            'self' => true,
            'allow' => [
                'https://fonts.bunny.net',
                'https://fonts.gstatic.com',
            ],
        ],

        'connect-src' => [
            'self' => true,
            'allow' => [
                'https://www.google.com/recaptcha/',
                'https://www.gstatic.com/recaptcha/',
                'https://www.recaptcha.net/recaptcha/',
                'https://nominatim.openstreetmap.org',
                ...$reverbConnectSources,
            ],
        ],

        'frame-src' => [
            'self' => true,
            'schemes' => ['https'],
            'allow' => [
                'https://www.google.com/recaptcha/',
                'https://www.gstatic.com/recaptcha/',
                'https://www.recaptcha.net/recaptcha/',
                'https://www.openstreetmap.org/',
            ],
        ],

        'frame-ancestors' => [
            'self' => true,
        ],

        'object-src' => [
            'none' => true,
        ],

        'base-uri' => [
            'self' => true,
        ],

        'form-action' => [
            'self' => true,
        ],

        'upgrade-insecure-requests' => true,
    ],
];

<?php

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
            'allow' => [
                'https://www.google.com/recaptcha/',
                'https://www.gstatic.com/recaptcha/',
            ],
        ],

        'style-src' => [
            'self' => true,
            'unsafe-inline' => true,
            'allow' => [
                'https://fonts.bunny.net',
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
            ],
        ],

        'connect-src' => [
            'self' => true,
            'allow' => [
                'https://www.google.com/recaptcha/',
            ],
        ],

        'frame-src' => [
            'self' => true,
            'allow' => [
                'https://www.google.com/recaptcha/',
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

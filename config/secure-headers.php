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

    'permissions-policy' => [
        'enable' => false,
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
            'unsafe-inline' => true,
            'unsafe-eval' => true,
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
            ],
        ],

        'frame-src' => [
            'self' => true,
            'allow' => [
                'https://www.google.com/recaptcha/',
                'https://www.gstatic.com/recaptcha/',
                'https://www.recaptcha.net/recaptcha/',
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

<?php

use App\Http\Middleware\GenerateDeviceFingerprint;
use App\Http\Middleware\ThrottleReportSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    if (! Route::has('test.livewire.submit')) {
        Route::middleware([
            GenerateDeviceFingerprint::class,
            ThrottleReportSubmission::class,
        ])->post('/livewire/update', function () {
            return response()->json(['ok' => true]);
        })->name('test.livewire.submit');
    }
});

function livewireReportSubmitPayload(): array
{
    return [
        'components' => [
            [
                'snapshot' => json_encode([
                    'memo' => [
                        'name' => 'components.report-form',
                    ],
                ], JSON_THROW_ON_ERROR),
                'calls' => [
                    [
                        'path' => '',
                        'method' => 'submit',
                        'params' => [],
                    ],
                ],
            ],
        ],
    ];
}

it('blocks sixth report submission from same ip within 15 minutes', function () {
    $headers = [
        'User-Agent' => 'RateLimit/1.0',
        'Accept-Language' => 'fr-CA',
    ];

    for ($i = 0; $i < 5; $i++) {
        $this->withHeaders($headers)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->postJson('/livewire/update', livewireReportSubmitPayload())
            ->assertOk();
    }

    $this->withHeaders($headers)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->postJson('/livewire/update', livewireReportSubmitPayload())
        ->assertStatus(429)
        ->assertJson([
            'message' => __('report.validation.rate_limit_ip'),
        ]);
});

it('blocks eleventh report submission from same device across rotating ips', function () {
    $headers = [
        'User-Agent' => 'RateLimit/2.0 SameDevice',
        'Accept-Language' => 'en-US',
    ];

    for ($i = 1; $i <= 10; $i++) {
        $this->withHeaders($headers)
            ->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
            ->postJson('/livewire/update', livewireReportSubmitPayload())
            ->assertOk();
    }

    $this->withHeaders($headers)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->postJson('/livewire/update', livewireReportSubmitPayload())
        ->assertStatus(429)
        ->assertJson([
            'message' => __('report.validation.rate_limit_device'),
        ]);
});


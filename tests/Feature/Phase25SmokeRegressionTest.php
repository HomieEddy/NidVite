<?php

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\ResponseCache\Middlewares\CacheResponse;

uses(RefreshDatabase::class);

it('keeps geojson payload shape stable for map consumers', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
        'is_spam' => false,
        'description' => 'Pothole near bike lane',
        'address' => '200 Rue Test',
    ]);
    $report->setLocation(45.5001, -73.6002);

    $response = $this->getJson(route('api.reports.geojson'));

    $response->assertOk();

    $feature = $response->json('features.0');

    expect($feature)->toHaveKey('type', 'Feature')
        ->and($feature)->toHaveKey('geometry.type', 'Point')
        ->and($feature)->toHaveKey('geometry.coordinates')
        ->and($feature)->toHaveKey('properties.tracking_id', $report->public_tracking_id)
        ->and($feature)->toHaveKey('properties.status', 'verified')
        ->and($feature)->toHaveKey('properties.url');
});

it('keeps tracking lookup payload shape stable for frontend progress rendering', function () {
    $report = Report::factory()->create([
        'status' => 'scheduled',
        'is_spam' => false,
    ]);
    $report->setLocation(45.5005, -73.6006);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk();

    expect($response->json())
        ->toHaveKey('tracking_id', $report->public_tracking_id)
        ->toHaveKey('status', 'scheduled')
        ->toHaveKey('progress.current_step')
        ->toHaveKey('progress.total_steps', 5)
        ->toHaveKey('steps');
});

it('keeps tracking lookup failure contract stable for unknown tracking ids', function () {
    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => 'missing-tracking-id']));

    $response->assertNotFound();
});

it('keeps geojson contract stable when no visible map reports exist', function () {
    Report::factory()->create([
        'status' => 'rejected',
        'is_spam' => false,
    ])->setLocation(45.5001, -73.6002);

    Report::factory()->create([
        'status' => 'verified',
        'is_spam' => true,
    ])->setLocation(45.5002, -73.6003);

    $response = $this->getJson(route('api.reports.geojson'));

    $response->assertOk();

    expect($response->json())
        ->toHaveKey('type', 'FeatureCollection')
        ->toHaveKey('features')
        ->and($response->json('features'))->toBeArray()
        ->and($response->json('features'))->toBeEmpty();
});

it('keeps homepage stats response contract stable', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $report = Report::factory()->create([
        'status' => 'repaired',
        'is_spam' => false,
        'created_at' => now()->subDays(5),
        'completed_at' => now()->subDays(2),
    ]);
    $report->setLocation(45.5111, -73.6111);

    $pending = Report::factory()->create([
        'status' => 'verified',
        'is_spam' => false,
    ]);
    $pending->setLocation(45.5222, -73.6222);

    $response = $this->get('/');

    $response->assertOk()->assertViewHasAll([
        'totalReported',
        'totalFixed',
        'totalPending',
        'velocity',
    ]);

    expect($response->viewData('totalReported'))->toBe(2)
        ->and($response->viewData('totalFixed'))->toBe(1)
        ->and($response->viewData('totalPending'))->toBe(1)
        ->and((string) $response->viewData('velocity'))->not->toBe(__('report.velocity_na'));
});

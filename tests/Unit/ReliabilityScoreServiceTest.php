<?php

use App\Models\Report;
use App\Services\Reports\ReliabilityScoreService;
use Tests\TestCase;

uses(TestCase::class);

it('computes deterministic reliability score snapshots', function () {
    $report = new Report([
        'description' => str_repeat('clear pothole details ', 4),
        'is_spam' => false,
        'geofence_passed' => true,
        'location_accuracy_passed' => true,
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
    ]);

    $service = app(ReliabilityScoreService::class);

    $first = $service->score($report);
    $second = $service->score($report);

    expect($first)->toBe($second)
        ->and($first['score'])->toBeInt()
        ->and($first['score'])->toBeGreaterThanOrEqual(0)
        ->and($first['score'])->toBeLessThanOrEqual(100)
        ->and($first['breakdown'])->toHaveKey('factors.road_validation');
});

it('penalizes clearly untrusted signals', function () {
    $trusted = new Report([
        'description' => str_repeat('good evidence ', 5),
        'is_spam' => false,
        'geofence_passed' => true,
        'location_accuracy_passed' => true,
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
    ]);

    $untrusted = new Report([
        'description' => 'bad',
        'is_spam' => true,
        'geofence_passed' => false,
        'location_accuracy_passed' => false,
        'location_source' => 'manual',
        'road_validation_decision' => 'fail_both',
    ]);

    $service = app(ReliabilityScoreService::class);

    $trustedScore = $service->score($trusted)['score'];
    $untrustedScore = $service->score($untrusted)['score'];

    expect($trustedScore)->toBeGreaterThan($untrustedScore);
});

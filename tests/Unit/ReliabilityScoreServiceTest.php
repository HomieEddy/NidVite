<?php

use App\Models\Report;
use App\Services\Reports\ReliabilityScoreService;
use Tests\TestCase;

uses(TestCase::class);

it('computes deterministic reliability score snapshots', function () {
    $report = (new Report)->forceFill([
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
    $trusted = (new Report)->forceFill([
        'description' => str_repeat('good evidence ', 5),
        'is_spam' => false,
        'geofence_passed' => true,
        'location_accuracy_passed' => true,
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
    ]);

    $untrusted = (new Report)->forceFill([
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

it('handles unknown signal values with safe defaults', function () {
    $report = (new Report)->forceFill([
        'description' => '',
        'is_spam' => false,
        'geofence_passed' => false,
        'location_accuracy_passed' => false,
        'location_source' => 'unknown-source',
        'road_validation_decision' => 'unknown-decision',
    ]);

    $score = app(ReliabilityScoreService::class)->score($report);

    expect($score['score'])->toBeInt()
        ->and($score['score'])->toBeGreaterThanOrEqual(0)
        ->and($score['score'])->toBeLessThanOrEqual(100)
        ->and($score['breakdown'])->toHaveKeys(['base_score', 'factors', 'raw_score', 'normalized_score'])
        ->and($score['breakdown']['factors'])->toHaveKeys(['source', 'road_validation']);
});

it('clamps score to bounds when config weights are extreme', function () {
    config()->set('reliability_scoring.base_score', 500);
    config()->set('reliability_scoring.weights.spam_penalty', 0);
    config()->set('reliability_scoring.weights.description_short_penalty', 0);

    $high = (new Report)->forceFill([
        'description' => str_repeat('a', 80),
        'is_spam' => false,
        'geofence_passed' => true,
        'location_accuracy_passed' => true,
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
    ]);

    $highScore = app(ReliabilityScoreService::class)->score($high);

    config()->set('reliability_scoring.base_score', -500);
    config()->set('reliability_scoring.weights.spam_penalty', -500);

    $low = (new Report)->forceFill([
        'description' => 'x',
        'is_spam' => true,
        'geofence_passed' => false,
        'location_accuracy_passed' => false,
        'location_source' => 'manual',
        'road_validation_decision' => 'fail_both',
    ]);

    $lowScore = app(ReliabilityScoreService::class)->score($low);

    expect($highScore['score'])->toBe(100)
        ->and($lowScore['score'])->toBe(0);
});

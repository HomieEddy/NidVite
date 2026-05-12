<?php

use App\Services\StreetProximityValidationService;
use Database\Seeders\MontrealRoadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MontrealRoadSeeder::class);
});

it('passes when point is near road and accuracy is good', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 80);
    config()->set('report_validation.max_location_accuracy_meters', 50);

    $result = (new StreetProximityValidationService)->validate(45.5017, -73.5673, 12.0);

    expect($result['decision'])->toBe('pass');
    expect($result['should_block'])->toBeFalse();
    expect($result['distance_meters'])->not->toBeNull();
});

it('fails off street when nearest road is outside threshold', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 5);
    config()->set('report_validation.max_location_accuracy_meters', 50);

    $result = (new StreetProximityValidationService)->validate(45.5120, -73.5945, 12.0);

    expect($result['decision'])->toBe('fail_off_street');
    expect($result['should_block'])->toBeTrue();
});

it('fails low accuracy when distance passes but accuracy exceeds threshold', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 80);
    config()->set('report_validation.max_location_accuracy_meters', 20);

    $result = (new StreetProximityValidationService)->validate(45.5017, -73.5673, 100.0);

    expect($result['decision'])->toBe('fail_low_accuracy');
    expect($result['should_block'])->toBeTrue();
});

it('fails low accuracy when accuracy is negative', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 80);
    config()->set('report_validation.max_location_accuracy_meters', 50);

    $result = (new StreetProximityValidationService)->validate(45.5017, -73.5673, -1.0);

    expect($result['decision'])->toBe('fail_low_accuracy');
    expect($result['accuracy_passed'])->toBeFalse();
    expect($result['should_block'])->toBeTrue();
});

it('fails both when off street and low accuracy', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 5);
    config()->set('report_validation.max_location_accuracy_meters', 20);

    $result = (new StreetProximityValidationService)->validate(45.5120, -73.5945, 100.0);

    expect($result['decision'])->toBe('fail_both');
    expect($result['should_block'])->toBeTrue();
});

it('passes when nearest-road distance is exactly on configured threshold', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 1000);
    config()->set('report_validation.max_location_accuracy_meters', 50);

    $baseline = (new StreetProximityValidationService)->validate(45.5017, -73.5673, 10.0);
    $distance = (float) ($baseline['distance_meters'] ?? 0.0);

    config()->set('report_validation.max_road_distance_meters', $distance);

    $result = (new StreetProximityValidationService)->validate(45.5017, -73.5673, 10.0);

    expect($result['decision'])->toBe('pass');
    expect($result['should_block'])->toBeFalse();
    expect($result['distance_meters'])->toBeGreaterThanOrEqual(0.0);
});

it('fails when nearest-road distance is just beyond strict threshold', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 0);
    config()->set('report_validation.max_location_accuracy_meters', 50);

    $result = (new StreetProximityValidationService)->validate(45.5019, -73.5673, 10.0);

    expect($result['decision'])->toBe('fail_off_street');
    expect($result['should_block'])->toBeTrue();
});

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

    $result = (new StreetProximityValidationService)->validate(45.40, -73.90, 12.0);

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

it('fails both when off street and low accuracy', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 5);
    config()->set('report_validation.max_location_accuracy_meters', 20);

    $result = (new StreetProximityValidationService)->validate(45.40, -73.90, 100.0);

    expect($result['decision'])->toBe('fail_both');
    expect($result['should_block'])->toBeTrue();
});

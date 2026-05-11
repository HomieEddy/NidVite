<?php

use App\Models\Report;
use App\Models\ReportCategory;
use App\Services\StreetProximityValidationService;
use Database\Seeders\MontrealRoadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MontrealRoadSeeder::class);

    ReportCategory::query()->firstOrCreate(
        ['slug' => 'pothole'],
        [
            'label_en' => 'Pothole',
            'label_fr' => 'Nid-de-poule',
            'icon' => 'circle-dot',
            'color' => '#EF4444',
            'sort_order' => 1,
            'is_active' => true,
        ]
    );
});

it('strict mode blocks failing submissions', function () {
    config()->set('report_validation.mode', 'strict');
    config()->set('report_validation.max_road_distance_meters', 5);
    config()->set('report_validation.max_location_accuracy_meters', 20);

    $result = (new StreetProximityValidationService)->validate(45.40, -73.90, 100.0);

    expect($result['decision'])->toBe('fail_both');
    expect($result['should_block'])->toBeTrue();
});

it('shadow mode accepts failing outcomes and stores metadata', function () {
    config()->set('report_validation.mode', 'shadow');
    config()->set('report_validation.max_road_distance_meters', 5);
    config()->set('report_validation.max_location_accuracy_meters', 20);

    $result = (new StreetProximityValidationService)->validate(45.40, -73.90, 100.0);

    expect($result['should_block'])->toBeFalse();

    $categoryId = (int) ReportCategory::query()
        ->where('slug', 'pothole')
        ->value('id');

    $report = Report::create([
        'reporter_email' => 'shadow-mode@example.com',
        'preferred_locale' => 'fr',
        'category_id' => $categoryId,
        'description' => 'Shadow mode test',
        'address' => 'Montreal',
    ]);

    $report->road_distance_meters = $result['distance_meters'];
    $report->road_validation_decision = $result['decision'];
    $report->road_validation_reason = $result['reason'];
    $report->road_validation_mode = $result['mode'];
    $report->location_accuracy_passed = $result['accuracy_passed'];
    $report->save();

    expect($report->road_validation_mode)->toBe('shadow');
    expect($report->road_validation_decision)->toBe('fail_both');
});

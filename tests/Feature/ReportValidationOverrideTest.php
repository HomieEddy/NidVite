<?php

use App\Actions\Reports\OverrideRoadValidationAction;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('requires an audit note for road validation override', function () {
    $report = Report::factory()->create([
        'road_validation_decision' => 'pass',
        'road_validation_reason' => 'pass',
        'road_validation_mode' => 'shadow',
        'location_accuracy_passed' => true,
    ]);

    expect(fn () => $report->overrideRoadValidation('fail_off_street', '   '))
        ->toThrow(InvalidArgumentException::class, 'Audit note is required for validation override');
});

it('rejects unknown road validation decisions during override', function () {
    $report = Report::factory()->create([
        'road_validation_decision' => 'pass',
        'road_validation_reason' => 'pass',
        'road_validation_mode' => 'shadow',
        'location_accuracy_passed' => true,
    ]);

    expect(fn () => $report->overrideRoadValidation('invalid_decision', 'Audit note'))
        ->toThrow(InvalidArgumentException::class, 'Invalid road_validation_decision provided for override');
});

it('stores override metadata and logs audit activity', function () {
    $report = Report::factory()->create([
        'road_validation_decision' => 'pass',
        'road_validation_reason' => 'pass',
        'road_validation_mode' => 'shadow',
        'location_accuracy_passed' => true,
    ]);

    $report->overrideRoadValidation('fail_low_accuracy', 'GPS drift observed in street canyon');

    $report->refresh();

    expect($report->road_validation_decision)->toBe('fail_low_accuracy')
        ->and($report->road_validation_reason)->toBe('admin_override')
        ->and($report->road_validation_mode)->toBe('admin_override')
        ->and($report->location_accuracy_passed)->toBeFalse();

    $activity = Activity::query()
        ->where('log_name', 'report_validation_override')
        ->where('subject_type', Report::class)
        ->where('subject_id', $report->getKey())
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old_decision'])->toBe('pass')
        ->and($activity->properties['new_decision'])->toBe('fail_low_accuracy')
        ->and($activity->properties['audit_note'])->toBe('GPS drift observed in street canyon');
});

it('delegates override mutation through OverrideRoadValidationAction', function () {
    $report = Report::factory()->create([
        'road_validation_decision' => 'pass',
        'road_validation_reason' => 'pass',
        'road_validation_mode' => 'shadow',
        'location_accuracy_passed' => true,
    ]);

    app(OverrideRoadValidationAction::class)(
        $report,
        'fail_off_street',
        'Visual check confirms off-street location'
    );

    $report->refresh();

    expect($report->road_validation_decision)->toBe('fail_off_street')
        ->and($report->road_validation_reason)->toBe('admin_override')
        ->and($report->road_validation_mode)->toBe('admin_override');
});

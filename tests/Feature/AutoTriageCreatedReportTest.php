<?php

use App\Events\ReportCreated;
use App\Listeners\AutoTriageCreatedReport;
use App\Models\Report;
use App\Models\ReportCategory;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('auto-verifies received reports that pass validation', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'category_id' => ReportCategory::query()->value('id'),
        'is_spam' => false,
        'road_validation_decision' => 'pass',
        'road_validation_mode' => 'enforce',
    ]);

    app(AutoTriageCreatedReport::class)->handle(new ReportCreated($report));

    expect($report->fresh()->status)->toBe('verified');
});

it('auto-rejects received reports flagged as spam', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'category_id' => ReportCategory::query()->value('id'),
        'is_spam' => true,
        'road_validation_decision' => 'pass',
        'road_validation_mode' => 'enforce',
    ]);

    app(AutoTriageCreatedReport::class)->handle(new ReportCreated($report));

    expect($report->fresh()->status)->toBe('rejected')
        ->and($report->fresh()->rejection_reason)->toBe('Automatically rejected by spam detection.');
});

it('auto-rejects received reports that fail enforce-mode road validation', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'category_id' => ReportCategory::query()->value('id'),
        'is_spam' => false,
        'road_validation_decision' => 'fail_both',
        'road_validation_mode' => 'enforce',
    ]);

    app(AutoTriageCreatedReport::class)->handle(new ReportCreated($report));

    expect($report->fresh()->status)->toBe('rejected')
        ->and($report->fresh()->rejection_reason)->toBe('Automatically rejected by road validation.');
});

it('does not auto-reject road validation failures in shadow mode', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'category_id' => ReportCategory::query()->value('id'),
        'is_spam' => false,
        'road_validation_decision' => 'fail_off_street',
        'road_validation_mode' => 'shadow',
    ]);

    app(AutoTriageCreatedReport::class)->handle(new ReportCreated($report));

    expect($report->fresh()->status)->toBe('verified');
});

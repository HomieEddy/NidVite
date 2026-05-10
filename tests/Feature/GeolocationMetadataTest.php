<?php

use App\Models\Report;
use Database\Seeders\MontrealBoundarySeeder;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
    $this->seed(MontrealBoundarySeeder::class);
});

describe('Report setLocation with accuracy and source', function () {
    it('stores gps accuracy and source when provided', function () {
        $report = Report::factory()->create(['reporter_email' => 'citizen@example.com']);
        $report->setLocation(45.5019, -73.5674, 10.5, 'gps');

        $report->refresh();
        expect($report->location_accuracy)->toBe(10.5)
            ->and($report->location_source)->toBe('gps');
    });

    it('stores geocode accuracy and source when provided', function () {
        $report = Report::factory()->create(['reporter_email' => 'citizen@example.com']);
        $report->setLocation(45.5019, -73.5674, 2500.0, 'geocode');

        $report->refresh();
        expect($report->location_accuracy)->toBe(2500.0)
            ->and($report->location_source)->toBe('geocode');
    });

    it('stores manual source without accuracy', function () {
        $report = Report::factory()->create(['reporter_email' => 'citizen@example.com']);
        $report->setLocation(45.5019, -73.5674, null, 'manual');

        $report->refresh();
        expect($report->location_source)->toBe('manual')
            ->and($report->location_accuracy)->toBeNull();
    });

    it('does not update when accuracy and source are null', function () {
        $report = Report::factory()->create(['reporter_email' => 'citizen@example.com']);
        $report->setLocation(45.5019, -73.5674);

        $report->refresh();
        expect($report->location_accuracy)->toBeNull()
            ->and($report->location_source)->toBeNull();
    });

    it('can create report with accuracy and source via fillable', function () {
        $report = Report::create([
            'reporter_email' => 'citizen@example.com',
            'preferred_locale' => 'fr',
            'status' => 'received',
            'priority' => 'normal',
            'category_id' => 1,
            'description' => 'Test pothole',
            'location_accuracy' => 15.0,
            'location_source' => 'gps',
        ]);
        $report->setLocation(45.5019, -73.5674);

        $report->refresh();
        expect($report->location_accuracy)->toBe(15.0)
            ->and($report->location_source)->toBe('gps');
    });
});

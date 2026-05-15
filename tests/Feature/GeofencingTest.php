<?php

use App\Models\MontrealBoundary;
use App\Models\Report;
use Database\Seeders\MontrealBoundarySeeder;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
    $this->seed(MontrealBoundarySeeder::class);
});

describe('MontrealBoundary', function () {
    it('contains a point inside Montreal', function () {
        // Downtown Montreal
        expect(MontrealBoundary::contains(45.5019, -73.5674))->toBeTrue();
    });

    it('does not contain a point outside Montreal', function () {
        // Toronto
        expect(MontrealBoundary::contains(43.6532, -79.3832))->toBeFalse();

        // New York
        expect(MontrealBoundary::contains(40.7128, -74.0060))->toBeFalse();
    });

    it('does not contain a point in Quebec City', function () {
        expect(MontrealBoundary::contains(46.8139, -71.2082))->toBeFalse();
    });
});

describe('Report geofence validation', function () {
    it('passes validation for a point inside Montreal', function () {
        expect(fn () => Report::validateGeofence(45.5019, -73.5674))->not->toThrow(Exception::class);
    });

    it('throws validation exception for a point outside Montreal', function () {
        expect(fn () => Report::validateGeofence(43.6532, -79.3832))
            ->toThrow(ValidationException::class);
    });
});

describe('Report creation with geofence', function () {
    it('creates a report with location inside Montreal', function () {
        $report = Report::factory()->create([
            'status' => 'received',
            'reporter_email' => 'citizen@example.com',
        ]);

        $report->setLocation(45.5019, -73.5674);

        expect($report->fresh())->not->toBeNull();
    });
});

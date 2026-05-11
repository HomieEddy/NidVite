<?php

use App\Models\MontrealBoundary;
use App\Models\Report;
use Database\Seeders\MontrealBoundarySeeder;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
    $this->seed(MontrealBoundarySeeder::class);
});

describe('MontrealBoundary geofence regression', function () {
    it('contains downtown montreal', function () {
        expect(MontrealBoundary::contains(45.5019, -73.5674))->toBeTrue();
    });

    it('contains plateau montreal', function () {
        expect(MontrealBoundary::contains(45.5218, -73.5790))->toBeTrue();
    });

    it('rejects toronto', function () {
        expect(MontrealBoundary::contains(43.6532, -79.3832))->toBeFalse();
    });

    it('rejects new york', function () {
        expect(MontrealBoundary::contains(40.7128, -74.0060))->toBeFalse();
    });

    it('rejects quebec city', function () {
        expect(MontrealBoundary::contains(46.8139, -71.2082))->toBeFalse();
    });
});

describe('Report validateGeofence regression', function () {
    it('accepts valid montreal coordinates', function () {
        expect(fn () => Report::validateGeofence(45.5019, -73.5674))->not->toThrow(Exception::class);
    });

    it('rejects coordinates outside montreal', function () {
        expect(fn () => Report::validateGeofence(43.6532, -79.3832))
            ->toThrow(ValidationException::class, 'Montreal');
    });

    it('accepts another point within montreal boundary', function () {
        expect(fn () => Report::validateGeofence(45.4696, -73.5556))->not->toThrow(Exception::class);
    });
});

describe('Report creation with geofence regression', function () {
    it('creates report with location inside montreal', function () {
        $report = Report::factory()->create([
            'status' => 'received',
            'reporter_email' => 'regression-test@example.com',
        ]);
        $report->setLocation(45.5019, -73.5674);

        expect($report->fresh())->not->toBeNull();
        expect(MontrealBoundary::contains(45.5019, -73.5674))->toBeTrue();
    });

    it('setLocation uses postgis correctly', function () {
        $report = Report::factory()->create([
            'status' => 'received',
            'reporter_email' => 'postgis-test@example.com',
        ]);
        $report->setLocation(45.5019, -73.5674);

        $report->refresh();
        $location = DB::selectOne('SELECT ST_X(location::geometry) as lng FROM reports WHERE id = ?', [$report->id]);
        expect($location->lng)->not->toBeNull();
    });
});

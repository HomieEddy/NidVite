<?php

use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\SuspiciousActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['broadcasting.default' => 'log']);
});

it('logs rapid repeat submissions as suspicious activity', function () {
    $fingerprint = str_repeat('a', 64);

    Report::factory()->count(3)->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.10',
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $newReport = Report::factory()->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.10',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    event(new ReportCreated($newReport));

    $this->assertDatabaseHas('suspicious_activities', [
        'report_id' => $newReport->getKey(),
        'type' => 'rapid_repeat_submission',
        'severity' => 'high',
    ]);
});

it('logs geolocation spoofing patterns for unrealistic movement', function () {
    $fingerprint = str_repeat('b', 64);

    $previous = Report::factory()->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.20',
        'created_at' => now()->subMinutes(3),
        'updated_at' => now()->subMinutes(3),
    ]);
    $previous->setLocation(45.5017, -73.5673);

    $current = Report::factory()->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.21',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $current->setLocation(45.5950, -73.7500);

    event(new ReportCreated($current));

    $activity = SuspiciousActivity::query()
        ->where('report_id', $current->getKey())
        ->where('type', 'geolocation_spoofing')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->severity)->toBe('critical');
});

it('logs off-street and near-miss road validation signals', function () {
    config()->set('report_validation.max_road_distance_meters', 35);
    config()->set('report_validation.near_miss_buffer_meters', 10);

    $report = Report::factory()->create([
        'road_validation_decision' => 'fail_off_street',
        'road_validation_reason' => 'off_street',
        'road_validation_mode' => 'shadow',
        'road_distance_meters' => 40.0,
        'location_accuracy_passed' => true,
    ]);

    event(new ReportCreated($report));

    $this->assertDatabaseHas('suspicious_activities', [
        'report_id' => $report->getKey(),
        'type' => 'road_validation_off_street',
        'severity' => 'high',
    ]);

    $this->assertDatabaseHas('suspicious_activities', [
        'report_id' => $report->getKey(),
        'type' => 'road_validation_near_miss',
        'severity' => 'medium',
    ]);
});

it('logs address-coordinate mismatch when geocoded location fails road validation', function () {
    $report = Report::factory()->create([
        'location_source' => 'geocode',
        'road_validation_decision' => 'fail_both',
        'road_validation_reason' => 'off_street_and_low_accuracy',
        'road_validation_mode' => 'shadow',
        'road_distance_meters' => 120.0,
        'location_accuracy_passed' => false,
    ]);

    event(new ReportCreated($report));

    $this->assertDatabaseHas('suspicious_activities', [
        'report_id' => $report->getKey(),
        'type' => 'address_coordinate_mismatch',
        'severity' => 'medium',
    ]);
});

it('does not log near-miss when road distance is beyond near-miss buffer', function () {
    config()->set('report_validation.max_road_distance_meters', 35);
    config()->set('report_validation.near_miss_buffer_meters', 10);

    $report = Report::factory()->create([
        'road_validation_decision' => 'fail_off_street',
        'road_validation_reason' => 'off_street',
        'road_validation_mode' => 'shadow',
        'road_distance_meters' => 60.0,
        'location_accuracy_passed' => true,
    ]);

    event(new ReportCreated($report));

    $this->assertDatabaseHas('suspicious_activities', [
        'report_id' => $report->getKey(),
        'type' => 'road_validation_off_street',
    ]);

    $this->assertDatabaseMissing('suspicious_activities', [
        'report_id' => $report->getKey(),
        'type' => 'road_validation_near_miss',
    ]);
});

it('does not log rapid repeat activity when submissions are below threshold', function () {
    $fingerprint = str_repeat('c', 64);

    Report::factory()->count(2)->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.30',
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $newReport = Report::factory()->create([
        'device_fingerprint_hash' => $fingerprint,
        'ip_address_raw' => '10.10.10.30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    event(new ReportCreated($newReport));

    $this->assertDatabaseMissing('suspicious_activities', [
        'report_id' => $newReport->getKey(),
        'type' => 'rapid_repeat_submission',
    ]);
});

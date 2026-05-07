<?php

use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\SuspiciousActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

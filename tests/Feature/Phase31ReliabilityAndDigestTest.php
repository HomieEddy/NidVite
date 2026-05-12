<?php

use App\Mail\WeeklyOperationsDigest;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('stores reliability snapshot fields when creating a report', function () {
    $report = Report::factory()->create([
        'description' => str_repeat('Detailed road issue evidence. ', 4),
        'is_spam' => false,
        'geofence_passed' => true,
        'location_accuracy_passed' => true,
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
    ]);

    $report->refresh();

    expect($report->reliability_score)->toBeInt()
        ->and($report->reliability_score)->toBeGreaterThanOrEqual(0)
        ->and($report->reliability_score)->toBeLessThanOrEqual(100)
        ->and($report->reliability_breakdown)->toBeArray()
        ->and($report->reliability_scored_at)->not->toBeNull();
});

it('recomputes reliability snapshot fields when updating a report', function () {
    $report = Report::factory()->create([
        'description' => str_repeat('Initial detail. ', 5),
        'is_spam' => false,
        'location_source' => 'manual',
    ]);

    $report->forceFill([
        'location_source' => 'gps',
        'road_validation_decision' => 'pass',
        'location_accuracy_passed' => true,
        'description' => str_repeat('Updated detailed evidence. ', 5),
    ])->save();

    $report->refresh();

    expect($report->reliability_score)->toBeInt()
        ->and($report->reliability_score)->toBeGreaterThanOrEqual(0)
        ->and($report->reliability_score)->toBeLessThanOrEqual(100)
        ->and($report->reliability_breakdown)->toBeArray()
        ->and($report->reliability_scored_at)->not->toBeNull();
});

it('queues weekly digest email with counts and hotspots', function () {
    Mail::fake();

    Config::set('operations_digest.recipients', ['ops@example.com']);
    Config::set('operations_digest.locale', 'en');
    Config::set('operations_digest.window_days', 7);

    Report::factory()->create([
        'status' => 'received',
        'borough' => 'Ville-Marie',
        'neighborhood' => 'Old Port',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    Report::factory()->create([
        'status' => 'repaired',
        'borough' => 'Ville-Marie',
        'neighborhood' => 'Old Port',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(1),
    ]);

    $exitCode = Artisan::call('reports:send-weekly-digest');

    expect($exitCode)->toBe(0);

    Mail::assertQueued(WeeklyOperationsDigest::class, function (WeeklyOperationsDigest $mail): bool {
        return isset($mail->summary['counts']['new'])
            && isset($mail->summary['counts']['open'])
            && isset($mail->summary['counts']['resolved'])
            && count($mail->summary['hotspots']['neighborhoods'] ?? []) > 0
            && count($mail->summary['hotspots']['zones'] ?? []) > 0;
    });
});

it('returns success without sending digest when recipients are not configured', function () {
    Mail::fake();

    Config::set('operations_digest.recipients', []);

    $exitCode = Artisan::call('reports:send-weekly-digest');

    expect($exitCode)->toBe(0);
    Mail::assertNothingQueued();
});

it('filters malformed recipients and still sends once to valid destination', function () {
    Mail::fake();

    Config::set('operations_digest.recipients', [
        'OPS@example.com',
        'ops@example.com',
        'not-an-email',
        ' ',
    ]);

    Report::factory()->create([
        'created_at' => now()->subHours(12),
        'updated_at' => now()->subHours(12),
    ]);

    $exitCode = Artisan::call('reports:send-weekly-digest');

    expect($exitCode)->toBe(0);
    Mail::assertQueued(WeeklyOperationsDigest::class, function (WeeklyOperationsDigest $mail): bool {
        return $mail->hasTo('ops@example.com');
    });
});

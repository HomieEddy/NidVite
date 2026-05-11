<?php

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\ResponseCache\Middlewares\CacheResponse;

uses(RefreshDatabase::class);

it('renders homepage stats from visible non-rejected, non-spam reports with locations', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $received = Report::factory()->create([
        'status' => 'received',
        'is_spam' => false,
    ]);
    $received->setLocation(45.501, -73.567);

    $repaired = Report::factory()->create([
        'status' => 'repaired',
        'is_spam' => false,
        'created_at' => now()->subDays(3),
        'completed_at' => now()->subDay(),
    ]);
    $repaired->setLocation(45.502, -73.568);

    $rejected = Report::factory()->create([
        'status' => 'rejected',
        'is_spam' => false,
    ]);
    $rejected->setLocation(45.503, -73.569);

    $spam = Report::factory()->create([
        'status' => 'verified',
        'is_spam' => true,
    ]);
    $spam->setLocation(45.504, -73.570);

    $withoutLocation = Report::factory()->create([
        'status' => 'verified',
        'is_spam' => false,
    ]);

    $response = $this->get('/');

    $response->assertOk()->assertViewHasAll([
        'totalReported',
        'totalFixed',
        'totalPending',
        'velocity',
    ]);

    expect($response->viewData('totalReported'))->toBe(2)
        ->and($response->viewData('totalFixed'))->toBe(1)
        ->and($response->viewData('totalPending'))->toBe(1);

    expect((string) $response->viewData('velocity'))->not->toBe('N/D');
    expect((string) $response->viewData('velocity'))->not->toBe('N/A');

    expect($withoutLocation->location)->toBeNull();
});

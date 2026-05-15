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

    $velocity = (string) $response->viewData('velocity');

    expect($velocity)->not->toBeIn(['N/D', 'N/A']);

    expect($withoutLocation->getAttribute('location'))->toBeNull();
});

it('renders zero stats with velocity not-available when no visible reports exist', function () {
    $this->withoutMiddleware(CacheResponse::class);

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

    Report::factory()->create([
        'status' => 'verified',
        'is_spam' => false,
    ]);

    $response = $this->get('/');

    $response->assertOk();

    expect($response->viewData('totalReported'))->toBe(0)
        ->and($response->viewData('totalFixed'))->toBe(0)
        ->and($response->viewData('totalPending'))->toBe(0)
        ->and($response->viewData('velocity'))->toBe(__('report.velocity_na'));
});

it('renders velocity not-available when visible reports are not repaired', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $pending = Report::factory()->create([
        'status' => 'verified',
        'is_spam' => false,
    ]);
    $pending->setLocation(45.505, -73.571);

    $response = $this->get('/');

    $response->assertOk();

    expect($response->viewData('totalReported'))->toBe(1)
        ->and($response->viewData('totalFixed'))->toBe(0)
        ->and($response->viewData('totalPending'))->toBe(1)
        ->and($response->viewData('velocity'))->toBe(__('report.velocity_na'));
});

it('shows dummy data notice on homepage in non-production environments', function () {
    $this->withoutMiddleware(CacheResponse::class);
    app()->detectEnvironment(fn () => 'testing');
    app()->setLocale('en');

    $this->get('/')
        ->assertOk()
        ->assertSee('Demo data')
        ->assertSee('dummy data for prototyping purposes');
});

it('hides dummy data notice on homepage in production', function () {
    $this->withoutMiddleware(CacheResponse::class);
    app()->detectEnvironment(fn () => 'production');
    app()->setLocale('en');

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Demo data')
        ->assertDontSee('dummy data for prototyping purposes');
});

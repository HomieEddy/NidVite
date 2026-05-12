<?php

use App\Actions\Reports\SubmitReportAction;
use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\ReportCategory;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('displays the public report form', function () {
    $response = $this->get('/signaler');

    $response->assertStatus(200)
        ->assertSee('Signaler');
});

it('renders duplicate, gps, and photo quality client-side hooks on report form', function () {
    $response = $this->get('/signaler');

    $response->assertOk()
        ->assertSee('data-action="duplicate-nudge"', false)
        ->assertSee('data-action="gps-warning"', false)
        ->assertSee('data-action="photo-quality-warning"', false)
        ->assertSee('data-action="photo-quality-severe"', false);
});

it('has active report categories for the form', function () {
    $categories = ReportCategory::where('is_active', true)->get();

    expect($categories->count())->toBeGreaterThan(0);
});

it('creates report through SubmitReportAction and dispatches ReportCreated', function () {
    Event::fake([ReportCreated::class]);

    $category = ReportCategory::query()->where('is_active', true)->firstOrFail();

    $validated = [
        'reporter_email' => 'citizen@example.com',
        'category_id' => $category->id,
        'description' => 'Large pothole near crosswalk',
        'address' => '123 Rue Saint-Catherine',
        'neighborhood' => 'Ville-Marie',
        'borough' => 'Ville-Marie',
    ];

    $validation = [
        'distance_meters' => 1.2,
        'decision' => 'pass',
        'reason' => 'pass',
        'mode' => 'enforce',
        'accuracy_passed' => true,
    ];

    $photo = UploadedFile::fake()->image('pothole.jpg');

    $report = app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        7.5,
        'gps',
        [$photo],
        $validation
    );

    expect($report)->toBeInstanceOf(Report::class)
        ->and($report->public_tracking_id)->toStartWith('MTL')
        ->and($report->road_validation_decision)->toBe('pass')
        ->and($report->road_validation_mode)->toBe('enforce');

    Event::assertDispatched(ReportCreated::class, fn (ReportCreated $event): bool => $event->report->is($report)
    );
});

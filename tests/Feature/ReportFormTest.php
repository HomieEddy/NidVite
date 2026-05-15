<?php

use App\Actions\Reports\SubmitReportAction;
use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\ReportCategory;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

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

it('hides manual location fields until gps fallback is needed', function () {
    $response = $this->get('/signaler');

    $response->assertOk()
        ->assertDontSee('id="report_address"', false)
        ->assertDontSee('id="neighborhood"', false)
        ->assertDontSee('id="borough"', false);
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

    if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
        $this->markTestSkipped('Image processing extension not available in current PHP runtime.');
    }

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

it('rejects invalid location source in SubmitReportAction without persisting report', function () {
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

    expect(fn () => app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        7.5,
        'invalid_source',
        [],
        $validation
    ))->toThrow(InvalidArgumentException::class);

    expect(Report::query()->count())->toBe(0);
    Event::assertNotDispatched(ReportCreated::class);
});

it('rejects negative location accuracy in SubmitReportAction without persisting report', function () {
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

    expect(fn () => app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        -0.5,
        'gps',
        [],
        $validation
    ))->toThrow(InvalidArgumentException::class);

    expect(Report::query()->count())->toBe(0);
    Event::assertNotDispatched(ReportCreated::class);
});

it('creates report with nullable manual location fields for accurate gps submissions', function () {
    Event::fake([ReportCreated::class]);

    $category = ReportCategory::query()->where('is_active', true)->firstOrFail();

    $validated = [
        'reporter_email' => 'citizen@example.com',
        'category_id' => $category->id,
        'description' => 'Large pothole near crosswalk',
        'address' => null,
        'neighborhood' => null,
        'borough' => null,
    ];

    $validation = [
        'distance_meters' => 1.2,
        'decision' => 'pass',
        'reason' => 'pass',
        'mode' => 'enforce',
        'accuracy_passed' => true,
    ];

    $report = app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        6.5,
        'gps',
        [],
        $validation
    );

    expect($report->address)->toBeNull()
        ->and($report->neighborhood)->toBeNull()
        ->and($report->borough)->toBeNull();

    Event::assertDispatched(ReportCreated::class, fn (ReportCreated $event): bool => $event->report->is($report));
});

it('rejects missing address for manual fallback submission source', function () {
    Event::fake([ReportCreated::class]);

    $category = ReportCategory::query()->where('is_active', true)->firstOrFail();

    $validated = [
        'reporter_email' => 'citizen@example.com',
        'category_id' => $category->id,
        'description' => 'Large pothole near crosswalk',
        'address' => null,
        'neighborhood' => null,
        'borough' => null,
        'manual_location_fallback' => true,
    ];

    $validation = [
        'distance_meters' => 1.2,
        'decision' => 'pass',
        'reason' => 'pass',
        'mode' => 'enforce',
        'accuracy_passed' => true,
    ];

    expect(fn () => app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        6.5,
        'manual',
        [],
        $validation
    ))->toThrow(ValidationException::class);

    expect(Report::query()->count())->toBe(0);
    Event::assertNotDispatched(ReportCreated::class);
});

it('rejects missing address for non-gps source submission', function () {
    Event::fake([ReportCreated::class]);

    $category = ReportCategory::query()->where('is_active', true)->firstOrFail();

    $validated = [
        'reporter_email' => 'citizen@example.com',
        'category_id' => $category->id,
        'description' => 'Large pothole near crosswalk',
        'address' => null,
        'neighborhood' => null,
        'borough' => null,
    ];

    $validation = [
        'distance_meters' => 1.2,
        'decision' => 'pass',
        'reason' => 'pass',
        'mode' => 'enforce',
        'accuracy_passed' => true,
    ];

    expect(fn () => app(SubmitReportAction::class)(
        $validated,
        45.501,
        -73.567,
        6.5,
        'cell_tower',
        [],
        $validation
    ))->toThrow(ValidationException::class);

    expect(Report::query()->count())->toBe(0);
    Event::assertNotDispatched(ReportCreated::class);
});

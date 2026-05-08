<?php

use App\Models\Report;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('generates a patterned public tracking id', function () {
    $report = Report::factory()->create();

    expect($report->public_tracking_id)->toMatch('/^MTL[A-Z0-9]{8}$/');
});

it('serves tracking page by public tracking id', function () {
    $report = Report::factory()->create(['status' => 'in_progress']);

    $this->get('/suivi/'.$report->public_tracking_id)
        ->assertOk()
        ->assertSee($report->public_tracking_id)
        ->assertDontSee($report->uuid);
});

it('returns tracking lookup payload keyed by public tracking id', function () {
    $report = Report::factory()->create();

    $this->getJson('/api/reports/'.$report->public_tracking_id.'/lookup')
        ->assertOk()
        ->assertJsonPath('tracking_id', $report->public_tracking_id)
        ->assertJsonMissingPath('uuid');
});

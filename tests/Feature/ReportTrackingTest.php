<?php

use App\Models\Report;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('displays a report tracking page by public tracking id', function () {
    $report = Report::factory()->create([
        'status' => 'in_progress',
        'address' => '123 Rue Saint-Catherine, Montréal',
    ]);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee($report->public_tracking_id)
        ->assertSee('En cours')
        ->assertSee('123 Rue Saint-Catherine, Montréal');
});

it('returns 404 for invalid tracking id', function () {
    $this->get('/suivi/invalid-tracking-id')->assertStatus(404);
});

it('shows correct timeline for received report', function () {
    $report = Report::factory()->create(['status' => 'received']);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Reçu')
        ->assertSee('Statut actuel');
});

it('shows correct timeline for repaired report', function () {
    $report = Report::factory()->create(['status' => 'repaired']);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Réparé')
        ->assertSee('Statut actuel');
});

it('shows rejection message for rejected report', function () {
    $report = Report::factory()->create([
        'status' => 'rejected',
        'rejection_reason' => 'Out of service area',
    ]);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Signalement rejeté')
        ->assertSee('Out of service area');
});

it('shows category label when present', function () {
    $report = Report::factory()->create();

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee($report->category->label_fr);
});

it('returns tracking lookup location as lat/lng object when report has coordinates', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
    ]);
    $report->setLocation(45.508, -73.561);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertJsonPath('location.lat', 45.508)
        ->assertJsonPath('location.lng', -73.561);
});

it('returns null location in tracking lookup when report has no coordinates', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
    ]);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertJsonPath('location', null);
});

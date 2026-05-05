<?php

use App\Models\Report;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('displays a report tracking page by uuid', function () {
    $report = Report::factory()->create([
        'status' => 'in_progress',
        'address' => '123 Rue Saint-Catherine, Montréal',
    ]);

    $response = $this->get("/suivi/{$report->uuid}");

    $response->assertStatus(200)
        ->assertSee($report->uuid)
        ->assertSee('En cours')
        ->assertSee('123 Rue Saint-Catherine, Montréal');
});

it('returns 404 for invalid uuid', function () {
    $this->get('/suivi/invalid-uuid')->assertStatus(404);
});

it('shows correct timeline for received report', function () {
    $report = Report::factory()->create(['status' => 'received']);

    $response = $this->get("/suivi/{$report->uuid}");

    $response->assertStatus(200)
        ->assertSee('Reçu')
        ->assertSee('Statut actuel');
});

it('shows correct timeline for repaired report', function () {
    $report = Report::factory()->create(['status' => 'repaired']);

    $response = $this->get("/suivi/{$report->uuid}");

    $response->assertStatus(200)
        ->assertSee('Réparé')
        ->assertSee('Statut actuel');
});

it('shows rejection message for rejected report', function () {
    $report = Report::factory()->create([
        'status' => 'rejected',
        'rejection_reason' => 'Out of service area',
    ]);

    $response = $this->get("/suivi/{$report->uuid}");

    $response->assertStatus(200)
        ->assertSee('Signalement rejeté')
        ->assertSee('Out of service area');
});

it('shows category label when present', function () {
    $report = Report::factory()->create();

    $response = $this->get("/suivi/{$report->uuid}");

    $response->assertStatus(200)
        ->assertSee($report->category->label_fr);
});

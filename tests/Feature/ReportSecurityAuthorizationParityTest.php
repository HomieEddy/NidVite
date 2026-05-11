<?php

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('redirects guests to login for report admin pages', function () {
    $report = Report::factory()->create();

    $this->get(ReportResource::getUrl('index'))
        ->assertRedirect(route('filament.admin.auth.login'));

    $this->get(ReportResource::getUrl('create'))
        ->assertRedirect(route('filament.admin.auth.login'));

    $this->get(ReportResource::getUrl('edit', ['record' => $report]))
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('denies viewer access to edit reports in filament', function () {
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);
    $report = Report::factory()->create();

    $this->actingAs($viewer)
        ->get(ReportResource::getUrl('edit', ['record' => $report]))
        ->assertForbidden();
});

it('allows manager access to edit reports in filament', function () {
    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);
    $report = Report::factory()->create();

    $this->actingAs($manager)
        ->get(ReportResource::getUrl('edit', ['record' => $report]))
        ->assertOk();
});

it('applies throttle middleware to geojson endpoint', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.22.22.22'])
            ->getJson(route('api.reports.geojson'))
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.22.22.22'])
        ->getJson(route('api.reports.geojson'))
        ->assertStatus(429);
});

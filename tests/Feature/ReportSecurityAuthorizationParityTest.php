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
        'role_id' => Role::query()->where('slug', 'viewer')->firstOrFail()->id,
        'is_active' => true,
    ]);
    $report = Report::factory()->create();

    $this->actingAs($viewer)
        ->get(ReportResource::getUrl('edit', ['record' => $report]))
        ->assertForbidden();
});

it('denies manager access to edit reports in filament', function () {
    $manager = User::factory()->create([
        'role_id' => Role::query()->where('slug', 'manager')->firstOrFail()->id,
        'is_active' => true,
    ]);
    $report = Report::factory()->create();

    $this->actingAs($manager)
        ->get(ReportResource::getUrl('edit', ['record' => $report]))
        ->assertForbidden();
});

it('applies throttle middleware to geojson endpoint', function () {
    $ip = '10.22.22.'.random_int(10, 250);

    for ($i = 0; $i < 60; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson(route('api.reports.geojson'))
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->getJson(route('api.reports.geojson'))
        ->assertStatus(429);
});

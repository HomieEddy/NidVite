<?php

use App\Filament\Resources\RepairJobs\Pages\CreateRepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);
});

it('shows the report tracking id and defaults status to planned on the create page', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    Livewire::test(CreateRepairJob::class)
        ->assertFormSet(['status' => 'planned'])
        ->assertSee($report->public_tracking_id);
});

it('limits scheduled_at to the latest linked report date', function () {
    $olderReport = Report::factory()->create([
        'status' => 'verified',
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);

    $newerReport = Report::factory()->create([
        'status' => 'verified',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    Livewire::test(CreateRepairJob::class)
        ->set('data.reports', [$olderReport->id, $newerReport->id])
        ->assertSchemaComponentExists('scheduled_at', 'form', function ($component) use ($newerReport): bool {
            return Carbon::parse($component->getMinDate())
                ->equalTo($newerReport->created_at);
        });
});
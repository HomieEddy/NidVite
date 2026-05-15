<?php

use App\Filament\Resources\RepairJobs\Pages\CreateRepairJob;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

it('rejects scheduled_at earlier than the latest linked report date on submit', function () {
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
        ->set('data.title', 'Validation failure job')
        ->set('data.status', 'planned')
        ->set('data.reports', [$olderReport->id, $newerReport->id])
        ->set('data.scheduled_at', now()->subDays(2)->toDateTimeString())
        ->call('create')
        ->assertHasErrors(['data.scheduled_at']);

    expect(RepairJob::query()->where('title', 'Validation failure job')->exists())->toBeFalse();
});

it('restricts create repair job page access for guests and unauthorized roles', function () {
    Auth::logout();

    $this->get(RepairJobResource::getUrl('create'))
        ->assertRedirect(route('filament.admin.auth.login'));

    /** @var User $viewer */
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($viewer)
        ->get(RepairJobResource::getUrl('create'))
        ->assertForbidden();
});

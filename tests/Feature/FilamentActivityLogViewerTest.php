<?php

use App\Filament\Pages\ActivityLogViewer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin users to access activity log viewer page', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);
    expect(ActivityLogViewer::canAccess())->toBeTrue();
});

it('forbids non-admin users from accessing activity log viewer page', function () {
    /** @var User $viewer */
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($viewer);
    expect(ActivityLogViewer::canAccess())->toBeFalse();
});

it('shows activity rows from the activity_log table', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $first = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'report.created',
        'causer_type' => User::class,
        'causer_id' => $admin->getKey(),
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $second = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'report.updated',
        'causer_type' => User::class,
        'causer_id' => $admin->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ActivityLogViewer::class)
        ->assertCanSeeTableRecords([$first, $second]);
});

it('filters activity rows by user, action, and date range', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    /** @var User $secondAdmin */
    $secondAdmin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $firstRecord = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'report.created',
        'causer_type' => User::class,
        'causer_id' => $admin->getKey(),
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $secondRecord = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'report.updated',
        'causer_type' => User::class,
        'causer_id' => $secondAdmin->getKey(),
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $thirdRecord = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'report.deleted',
        'causer_type' => User::class,
        'causer_id' => $admin->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ActivityLogViewer::class)
        ->filterTable('causer_id', ['value' => (string) $secondAdmin->getKey()])
        ->assertCanSeeTableRecords([$secondRecord])
        ->assertCanNotSeeTableRecords([$firstRecord, $thirdRecord])
        ->resetTableFilters()
        ->filterTable('description', ['value' => 'report.deleted'])
        ->assertCanSeeTableRecords([$thirdRecord])
        ->assertCanNotSeeTableRecords([$firstRecord, $secondRecord])
        ->resetTableFilters()
        ->filterTable('created_at', [
            'from' => now()->subDays(2)->toDateString(),
            'until' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$secondRecord, $thirdRecord])
        ->assertCanNotSeeTableRecords([$firstRecord]);
});

it('resolves localized labels for the activity log viewer', function () {
    app()->setLocale('fr');
    expect(__('filament.activity_log.heading'))->toBe('Journal d\'activite');

    app()->setLocale('en');
    expect(__('filament.activity_log.heading'))->toBe('Activity Log');
});

<?php

use App\Filament\Pages\SuspiciousActivityDashboard;
use App\Models\Role;
use App\Models\SuspiciousActivity;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin users to access suspicious activity dashboard', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    expect(SuspiciousActivityDashboard::canAccess())->toBeTrue();
});

it('forbids non-admin users from accessing suspicious activity dashboard', function () {
    /** @var User $viewer */
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($viewer);

    expect(SuspiciousActivityDashboard::canAccess())->toBeFalse();
});

it('forbids guest access to suspicious activity dashboard', function () {
    Auth::logout();

    expect(SuspiciousActivityDashboard::canAccess())->toBeFalse();
});

it('forbids manager, service worker, and accountant roles from suspicious dashboard', function () {
    $restrictedSlugs = ['manager', 'service_worker', 'accountant'];

    foreach ($restrictedSlugs as $slug) {
        /** @var User $user */
        $user = User::factory()->create([
            'role_id' => Role::where('slug', $slug)->value('id'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        expect(SuspiciousActivityDashboard::canAccess())->toBeFalse();
    }
});

it('shows suspicious activity rows in the dashboard table', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $record = SuspiciousActivity::query()->create([
        'type' => 'rapid_repeat_submission',
        'severity' => 'high',
        'reason' => 'Rapid repeat submissions detected',
        'metadata' => ['matching_reports_count' => 4],
    ]);

    $this->actingAs($admin);

    Livewire::test(SuspiciousActivityDashboard::class)
        ->assertCanSeeTableRecords([$record]);
});

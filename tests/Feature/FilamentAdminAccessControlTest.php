<?php

use App\Filament\Pages\ActivityLogViewer;
use App\Filament\Pages\SuspiciousActivityDashboard;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('redirects guests to admin login for protected filament pages', function () {
    $this->get(ActivityLogViewer::getUrl())
        ->assertRedirect(route('filament.admin.auth.login'));

    $this->get(SuspiciousActivityDashboard::getUrl())
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('allows active admins to access protected filament pages', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    expect(ActivityLogViewer::canAccess())->toBeTrue();
    expect(SuspiciousActivityDashboard::canAccess())->toBeTrue();
});

it('denies viewer access to protected filament pages', function () {
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($viewer);

    expect(ActivityLogViewer::canAccess())->toBeFalse();
    expect(SuspiciousActivityDashboard::canAccess())->toBeFalse();
});

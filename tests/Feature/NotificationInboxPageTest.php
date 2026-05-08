<?php

use App\Filament\Pages\NotificationInbox;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin and manager to access notification inbox', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);
    expect(NotificationInbox::canAccess())->toBeTrue();

    $this->actingAs($manager);
    expect(NotificationInbox::canAccess())->toBeTrue();
});

it('forbids viewer from notification inbox', function () {
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($viewer);

    expect(NotificationInbox::canAccess())->toBeFalse();
});

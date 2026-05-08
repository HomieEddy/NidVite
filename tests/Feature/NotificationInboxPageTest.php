<?php

use App\Filament\Pages\NotificationInbox;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;

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

it('forbids inactive admin and manager from notification inbox', function () {
    $inactiveAdmin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => false,
    ]);

    $inactiveManager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => false,
    ]);

    $this->actingAs($inactiveAdmin);
    expect(NotificationInbox::canAccess())->toBeFalse();

    $this->actingAs($inactiveManager);
    expect(NotificationInbox::canAccess())->toBeFalse();
});

it('redirects unauthenticated users to admin login for notification inbox', function () {
    $this->get(NotificationInbox::getUrl())
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('renders notifications for authorized users', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'critical_report_alert',
        'notifiable_type' => $admin::class,
        'notifiable_id' => $admin->id,
        'data' => [
            'message_key' => 'filament.notifications.critical_report.message',
            'tracking_id' => 'MTLTEST01',
        ],
    ]);

    $this->actingAs($admin)
        ->get(NotificationInbox::getUrl());

    Livewire::test(NotificationInbox::class)
        ->assertCanSeeTableRecords(DatabaseNotification::query()->get());
});

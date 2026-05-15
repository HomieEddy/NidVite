<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates admin user using configured seed credentials', function () {
    config()->set('admin-auth.admin_seed_email', 'seed-admin@example.test');
    config()->set('admin-auth.admin_seed_password', 'seed-admin-password');

    app(RoleSeeder::class)->run();
    app(AdminUserSeeder::class)->run();

    $admin = User::query()->where('email', 'seed-admin@example.test')->firstOrFail();

    expect($admin->role_id)->toBe(Role::query()->where('slug', 'admin')->value('id'))
        ->and(Hash::check('seed-admin-password', (string) $admin->password))->toBeTrue()
        ->and($admin->is_active)->toBeTrue();
});

it('fails fast when admin role is missing', function () {
    config()->set('admin-auth.admin_seed_password', 'seed-admin-password');

    expect(fn () => app(AdminUserSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Admin role is required before running AdminUserSeeder.');
});

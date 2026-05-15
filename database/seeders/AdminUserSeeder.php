<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates the default admin account for the Filament dashboard.
 *
 * Credentials come from config/admin-auth.php:
 * - admin_seed_email
 * - admin_seed_password
 *
 * If no seed password is configured, a random password is generated to avoid
 * committing shared credentials in source control.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::query()->where('slug', 'admin')->value('id');
        if (! is_int($adminRoleId)) {
            throw new RuntimeException('Admin role is required before running AdminUserSeeder.');
        }

        $adminEmail = (string) config('admin-auth.admin_seed_email', 'admin@nidvite.ca');
        $adminPassword = (string) (config('admin-auth.admin_seed_password') ?: Str::password(24));

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrateur',
                'password' => $adminPassword,
                'role_id' => $adminRoleId,
                'locale' => 'fr',
                'is_active' => true,
            ]
        );
    }
}

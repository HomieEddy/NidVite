<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the default admin account for the Filament dashboard.
 *
 * Credentials:
 *   Email:    admin@nidvite.ca
 *   Password: changeme-strong-password-2026
 *
 * IMPORTANT: Change this password after first login in production.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@nidvite.ca'],
            [
                'name' => 'Administrateur',
                'password' => bcrypt('changeme-strong-password-2026'),
                'role_id' => 1, // Admin
                'locale' => 'fr',
                'is_active' => true,
            ]
        );
    }
}

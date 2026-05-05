<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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

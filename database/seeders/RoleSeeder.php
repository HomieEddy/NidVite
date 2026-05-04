<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['slug' => 'admin', 'label_en' => 'Administrator', 'label_fr' => 'Administrateur', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'manager', 'label_en' => 'Manager', 'label_fr' => 'Gestionnaire', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'service_worker', 'label_en' => 'Service Worker', 'label_fr' => 'Travailleur de service', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'accountant', 'label_en' => 'Accountant', 'label_fr' => 'Comptable', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'viewer', 'label_en' => 'Viewer', 'label_fr' => 'Lecteur', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

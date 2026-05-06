<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ReportCategorySeeder::class,
            ExpenseCategorySeeder::class,
            MontrealBoundarySeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}

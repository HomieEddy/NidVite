<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('staging')) {
            $this->call([
                StagingDemoSeeder::class,
            ]);

            return;
        }

        $this->call([
            RoleSeeder::class,
            ReportCategorySeeder::class,
            MontrealBoundarySeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}

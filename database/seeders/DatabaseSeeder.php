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

        $seeders = [
            RoleSeeder::class,
            ReportCategorySeeder::class,
            MontrealBoundarySeeder::class,
            MontrealRoadSeeder::class,
            AdminUserSeeder::class,
        ];

        if (app()->environment(['local', 'testing'])) {
            $seeders[] = TestDataSeeder::class;
        }

        $this->call($seeders);
    }
}

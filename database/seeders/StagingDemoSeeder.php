<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StagingDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['staging', 'testing'])) {
            throw new RuntimeException('StagingDemoSeeder can only run in staging/testing environments.');
        }

        if (app()->environment('staging') && empty(config('admin-auth.staging_demo_seed_password'))) {
            throw new RuntimeException('staging_demo_seed_password must be configured for staging environment');
        }

        $demoPassword = (string) (config('admin-auth.staging_demo_seed_password') ?: Str::password(24));

        DB::transaction(function () use ($demoPassword): void {
            DB::table('job_reports')->delete();
            DB::table('email_delivery_logs')->delete();
            DB::table('suspicious_activities')->delete();
            DB::table('reports')->delete();

            // Staging demo should not keep operations data.
            DB::table('job_workers')->delete();
            DB::table('job_materials')->delete();
            DB::table('material_purchases')->delete();
            DB::table('expenses')->delete();
            DB::table('repair_jobs')->delete();

            DB::table('montreal_boundary')->delete();
            DB::table('materials')->delete();
            DB::table('vendors')->delete();
            DB::table('users')->delete();
            DB::table('roles')->delete();
            DB::table('report_categories')->delete();
            DB::table('activity_log')->delete();

            // Create Roles
            DB::table('roles')->updateOrInsert(
                ['slug' => 'admin'],
                ['label_en' => 'Administrator', 'label_fr' => 'Administrateur', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['slug' => 'manager'],
                ['label_en' => 'Manager', 'label_fr' => 'Gestionnaire', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['slug' => 'service_worker'],
                ['label_en' => 'Service Worker', 'label_fr' => 'Travailleur de service', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['slug' => 'accountant'],
                ['label_en' => 'Accountant', 'label_fr' => 'Comptable', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['slug' => 'viewer'],
                ['label_en' => 'Viewer', 'label_fr' => 'Lecteur', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()]
            );

            // Create Users
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
            $managerRoleId = DB::table('roles')->where('slug', 'manager')->value('id');

            User::updateOrCreate(
                ['email' => 'admin@nidvite.ca'],
                [
                    'name' => 'Administrateur',
                    'password' => $demoPassword,
                    'role_id' => $adminRoleId,
                    'locale' => 'fr',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'marquize.7@nidvite.ca'],
                [
                    'name' => 'Saad Tekiout',
                    'password' => $demoPassword,
                    'role_id' => $managerRoleId,
                    'locale' => 'fr',
                    'is_active' => true,
                ]
            );

            // Create Montreal Boundary
            $polygon = 'POLYGON((-73.95 45.52, -73.92 45.60, -73.85 45.68, -73.75 45.70, -73.65 45.68, -73.55 45.65, -73.48 45.60, -73.45 45.52, -73.48 45.45, -73.55 45.40, -73.65 45.42, -73.75 45.43, -73.85 45.45, -73.92 45.48, -73.95 45.52))';
            DB::insert('INSERT INTO montreal_boundary (name, boundary, created_at, updated_at) VALUES (?, ST_GeomFromText(?, 4326), ?, ?)', [
                'Island of Montreal', $polygon, now(), now(),
            ]);

            // Keep demo seed deterministic with no audit residue.
            DB::table('activity_log')->delete();
        });

        $this->call(TestDataSeeder::class);
    }
}

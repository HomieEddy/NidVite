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

        $demoPassword = (string) (config('admin-auth.staging_demo_seed_password') ?: Str::password(24));

        DB::transaction(function () use ($demoPassword): void {
            // Staging must contain no reports.
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

            // Reset demo entities for idempotent re-runs.
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

            // Create Report Categories
            DB::insert('INSERT INTO report_categories (slug, label_en, label_fr, icon, color, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'pothole', 'Pothole', 'Nid-de-poule', 'circle-dot', '#EF4444', 1, true, now(), now(),
            ]);

            // Create Material
            DB::insert('INSERT INTO materials (sku, name, description, unit, current_stock, reserved_stock, min_stock_alert, avg_purchase_price, last_purchase_price, location, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'ASPHALT-BAGS', 'Asphalt Bags', 'Standard asphalt repair bags', 'bag', 100.0, 0.0, 20.0, '45.00', '48.50', 'Warehouse A', true, now(), now(),
            ]);

            // Create Vendor
            DB::insert('INSERT INTO vendors (name, contact_name, email, phone, address, website, notes, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'Test Vendor', 'Test Contact', 'test@vendor.local', '+1-555-0100', '123 Test Street, Montreal, QC', 'https://test-vendor.local', 'Dummy vendor', true, now(), now(),
            ]);

            // Create Montreal Boundary
            $polygon = 'POLYGON((-73.95 45.52, -73.92 45.60, -73.85 45.68, -73.75 45.70, -73.65 45.68, -73.55 45.65, -73.48 45.60, -73.45 45.52, -73.48 45.45, -73.55 45.40, -73.65 45.42, -73.75 45.43, -73.85 45.45, -73.92 45.48, -73.95 45.52))';
            DB::insert('INSERT INTO montreal_boundary (name, boundary, created_at, updated_at) VALUES (?, ST_GeomFromText(?, 4326), ?, ?)', [
                'Island of Montreal', $polygon, now(), now(),
            ]);

            // Keep demo seed deterministic with no audit residue.
            DB::table('activity_log')->delete();
        });
    }
}

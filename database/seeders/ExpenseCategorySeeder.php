<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('expense_categories')->insert([
            ['slug' => 'materials', 'label_fr' => 'Matériaux', 'label_en' => 'Materials', 'color' => '#EF4444', 'is_inventory_related' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'labor', 'label_fr' => 'Main-d\'œuvre', 'label_en' => 'Labor', 'color' => '#3B82F6', 'is_inventory_related' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'fuel', 'label_fr' => 'Carburant', 'label_en' => 'Fuel', 'color' => '#F59E0B', 'is_inventory_related' => false, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'equipment_rental', 'label_fr' => 'Location d\'équipement', 'label_en' => 'Equipment Rental', 'color' => '#10B981', 'is_inventory_related' => false, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'transport', 'label_fr' => 'Transport', 'label_en' => 'Transport', 'color' => '#8B5CF6', 'is_inventory_related' => false, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'other', 'label_fr' => 'Autre', 'label_en' => 'Other', 'color' => '#6B7280', 'is_inventory_related' => false, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('report_categories')->insert([
            ['slug' => 'pothole', 'label_en' => 'Pothole', 'label_fr' => 'Nid-de-poule', 'icon' => 'circle-dot', 'color' => '#EF4444', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'graffiti', 'label_en' => 'Graffiti', 'label_fr' => 'Graffiti', 'icon' => 'spray-can', 'color' => '#F59E0B', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'broken_light', 'label_en' => 'Broken Street Light', 'label_fr' => 'Lampadaire brisé', 'icon' => 'lightbulb', 'color' => '#3B82F6', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'sidewalk', 'label_en' => 'Sidewalk Damage', 'label_fr' => 'Trottoir endommagé', 'icon' => 'person-walking', 'color' => '#10B981', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'other', 'label_en' => 'Other', 'label_fr' => 'Autre', 'icon' => 'help-circle', 'color' => '#6B7280', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

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
        ]);
    }
}

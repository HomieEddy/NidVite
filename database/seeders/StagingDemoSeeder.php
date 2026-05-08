<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Seeder;

class StagingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $received = Report::factory()->count(12)->create([
            'status' => 'received',
            'is_spam' => false,
            'neighborhood' => 'Montreal',
            'borough' => 'Ville-Marie',
        ]);

        $scheduled = Report::factory()->count(8)->create([
            'status' => 'scheduled',
            'is_spam' => false,
            'neighborhood' => 'Montreal',
            'borough' => 'Le Plateau-Mont-Royal',
            'first_scheduled_at' => now()->subDays(2),
        ]);

        $repaired = Report::factory()->count(10)->create([
            'status' => 'repaired',
            'is_spam' => false,
            'neighborhood' => 'Montreal',
            'borough' => 'Rosemont-La Petite-Patrie',
            'first_scheduled_at' => now()->subDays(7),
            'first_started_at' => now()->subDays(6),
            'completed_at' => now()->subDays(3),
        ]);

        $allReports = $received->concat($scheduled)->concat($repaired);

        foreach ($allReports as $index => $report) {
            $lat = 45.50 + (($index % 5) * 0.01);
            $lng = -73.57 + (($index % 5) * 0.01);
            $report->setLocation($lat, $lng);
        }
    }
}

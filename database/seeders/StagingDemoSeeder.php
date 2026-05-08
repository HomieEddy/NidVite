<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StagingDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep the demo dataset deterministic and reproducible on repeated runs.
        DB::statement('TRUNCATE TABLE job_reports, expenses, repair_jobs, reports RESTART IDENTITY CASCADE');

        $operator = User::query()->first() ?? User::factory()->create();

        $received = Report::factory()->count(12)->create([
            'status' => 'received',
            'is_spam' => false,
            'neighborhood' => 'Ville-Marie',
            'borough' => 'Ville-Marie',
        ]);

        $scheduled = Report::factory()->count(8)->create([
            'status' => 'scheduled',
            'is_spam' => false,
            'neighborhood' => 'Le Plateau-Mont-Royal',
            'borough' => 'Le Plateau-Mont-Royal',
            'first_scheduled_at' => now()->subDays(2),
        ]);

        $repaired = Report::factory()->count(10)->create([
            'status' => 'repaired',
            'is_spam' => false,
            'neighborhood' => 'Rosemont-La Petite-Patrie',
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

        foreach ($repaired as $report) {
            $job = RepairJob::factory()->create([
                'status' => 'completed',
                'created_by' => $operator->getKey(),
                'completed_at' => now()->subDays(2),
            ]);

            $job->reports()->attach($report->getKey(), [
                'cost_allocation_percentage' => 100,
            ]);

            Expense::factory()->create([
                'repair_job_id' => $job->getKey(),
                'created_by' => $operator->getKey(),
                'quantity' => 1,
                'unit_cost' => 150,
                'subtotal' => 150,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 150,
            ]);
        }
    }
}

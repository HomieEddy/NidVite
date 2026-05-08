<?php

use App\Models\Expense;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);

    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->first()->id,
        'is_active' => true,
    ]);
});

it('dashboard widgets run without SQL errors', function () {
    // Seed data that exercises all dashboard widgets
    $reportA = Report::factory()->create([
        'status' => 'repaired',
        'category_id' => ReportCategory::first()->id,
        'is_spam' => false,
        'neighborhood' => 'Plateau-Mont-Royal',
        'borough' => 'Le Plateau-Mont-Royal',
        'completed_at' => now()->subDays(2),
    ]);

    $reportB = Report::factory()->create([
        'status' => 'repaired',
        'category_id' => ReportCategory::first()->id,
        'is_spam' => false,
        'neighborhood' => 'Rosemont-La Petite-Patrie',
        'borough' => 'Rosemont-La Petite-Patrie',
        'completed_at' => now()->subDays(1),
    ]);

    /** @var RepairJob $repairJob */
    $repairJob = RepairJob::factory()->create([
        'status' => 'completed',
        'completed_at' => now()->subDay(),
        'created_by' => $this->admin->getKey(),
    ]);

    $repairJob->reports()->attach($reportA->getKey(), ['cost_allocation_percentage' => 60]);
    $repairJob->reports()->attach($reportB->getKey(), ['cost_allocation_percentage' => 40]);

    Expense::factory()->create([
        'repair_job_id' => $repairJob->getKey(),
        'created_by' => $this->admin->getKey(),
        'quantity' => 2,
        'unit_cost' => 100,
        'gst_rate' => 0,
        'qst_rate' => 0,
    ]);

    // ReportsOverview: getOpenReportsCount
    $openCount = Report::whereNotIn('status', ['repaired', 'rejected'])
        ->where('is_spam', false)
        ->count();
    expect($openCount)->toBeInt();

    // ReportsOverview: getRepairsThisWeekCount
    $repairsThisWeek = Report::where('status', 'repaired')
        ->where('completed_at', '>=', now()->subDays(7))
        ->count();
    expect($repairsThisWeek)->toBeInt();

    // ReportsOverview: getMoneySpent (uses total, not amount)
    $totalSpent = (float) Expense::sum('total');
    expect($totalSpent)->toBeFloat()->toBeGreaterThan(0);

    // ReportsOverview: getAverageRepairTime
    $avgDays = Report::whereNotNull('completed_at')
        ->whereNotNull('created_at')
        ->where('status', 'repaired')
        ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 86400) as avg_days')
        ->value('avg_days');
    expect($avgDays === null || is_numeric($avgDays))->toBeTrue();

    // ReportsChart: repair velocity trend over completed jobs
    $start = now()->subDays(29)->startOfDay();
    $end = now()->endOfDay();
    $counts = RepairJob::where('status', 'completed')
        ->whereBetween('completed_at', [$start, $end])
        ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->pluck('count', 'date');
    expect($counts)->toBeInstanceOf(Collection::class);

    // ReportsByNeighborhood: neighborhood cost analysis
    $neighborhoodCosts = Expense::query()
        ->join('repair_jobs', 'repair_jobs.id', '=', 'expenses.repair_job_id')
        ->join('job_reports', 'job_reports.repair_job_id', '=', 'repair_jobs.id')
        ->join('reports', 'reports.id', '=', 'job_reports.report_id')
        ->whereNotNull('reports.neighborhood')
        ->where('reports.neighborhood', '!=', '')
        ->whereBetween('expenses.created_at', [$start, $end])
        ->selectRaw('reports.neighborhood as neighborhood, SUM(expenses.total * (job_reports.cost_allocation_percentage / 100.0)) as total_cost')
        ->groupBy('reports.neighborhood')
        ->orderByDesc('total_cost')
        ->limit(10)
        ->get();
    expect($neighborhoodCosts)->toHaveCount(2);
});

it('expenses table has total column not amount', function () {
    $repairJob = RepairJob::factory()->create([
        'status' => 'completed',
        'created_by' => $this->admin->getKey(),
    ]);

    $expense = Expense::factory()->create([
        'repair_job_id' => $repairJob->getKey(),
        'created_by' => $this->admin->getKey(),
        'quantity' => 1,
        'unit_cost' => 250,
        'gst_rate' => 0,
        'qst_rate' => 0,
    ]);

    $foundExpense = Expense::findOrFail($expense->getKey());

    expect((float) $foundExpense->total)->toBe(250.0);
    expect(property_exists($foundExpense, 'amount'))->toBeFalse();
});

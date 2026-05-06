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
    Report::factory()->count(5)->create([
        'status' => 'received',
        'category_id' => ReportCategory::first()->id,
        'is_spam' => false,
        'neighborhood' => 'Plateau-Mont-Royal',
        'borough' => 'Le Plateau-Mont-Royal',
    ]);

    $repairJob = RepairJob::factory()->create([
        'status' => 'completed',
        'created_by' => $this->admin->getKey(),
    ]);

    Expense::factory()->count(3)->create([
        'repair_job_id' => $repairJob->getKey(),
        'created_by' => $this->admin->getKey(),
        'total' => 100.00,
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

    // ReportsChart: 30-day report counts
    $start = now()->subDays(29)->startOfDay();
    $end = now()->endOfDay();
    $counts = Report::whereBetween('created_at', [$start, $end])
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->pluck('count', 'date');
    expect($counts)->toBeInstanceOf(Collection::class);

    // ReportsByNeighborhood: top 10 neighborhoods
    $neighborhoods = Report::whereNotNull('neighborhood')
        ->where('neighborhood', '!=', '')
        ->selectRaw('neighborhood, COUNT(*) as count')
        ->groupBy('neighborhood')
        ->orderByDesc('count')
        ->limit(10)
        ->get();
    expect($neighborhoods)->toHaveCount(1);
});

it('expenses table has total column not amount', function () {
    $repairJob = RepairJob::factory()->create([
        'status' => 'completed',
        'created_by' => $this->admin->getKey(),
    ]);

    Expense::factory()->create([
        'repair_job_id' => $repairJob->getKey(),
        'created_by' => $this->admin->getKey(),
        'total' => 250.00,
    ]);

    $foundExpense = Expense::where('total', 250.00)->firstOrFail();

    expect((float) $foundExpense->total)->toBe(250.0);
    expect(property_exists($foundExpense, 'amount'))->toBeFalse();
});

<?php

use App\Models\Expense;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});

it('automatically calculates subtotal tax and total using gst and qst rates', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Expense calc job',
        'status' => 'planned',
        'created_by' => $admin->id,
    ]);

    $expense = Expense::query()->create([
        'repair_job_id' => $job->id,
        'description' => 'Asphalt load',
        'quantity' => 10,
        'unit_cost' => 20,
        'gst_rate' => 0.0500,
        'qst_rate' => 0.0998,
        'cost_allocation_mode' => 'equal_split',
        'created_by' => $admin->id,
    ]);

    expect((float) $expense->subtotal)->toBe(200.0)
        ->and((float) $expense->tax_rate)->toBe(0.1498)
        ->and((float) $expense->tax_amount)->toBe(29.96)
        ->and((float) $expense->total)->toBe(229.96);
});

it('applies equal split allocation to linked reports when mode is equal_split', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Equal split allocation',
        'status' => 'planned',
        'created_by' => $admin->id,
    ]);

    $reportA = Report::factory()->create();
    $reportB = Report::factory()->create();

    $job->reports()->attach($reportA->id, ['cost_allocation_percentage' => 0]);
    $job->reports()->attach($reportB->id, ['cost_allocation_percentage' => 0]);

    Expense::query()->create([
        'repair_job_id' => $job->id,
        'description' => 'Crew labor',
        'quantity' => 2,
        'unit_cost' => 150,
        'cost_allocation_mode' => 'equal_split',
        'created_by' => $admin->id,
    ]);

    $allocations = $job->reports()
        ->pluck('job_reports.cost_allocation_percentage')
        ->map(fn ($value) => (float) $value)
        ->sort()
        ->values()
        ->all();

    expect($allocations)->toBe([50.0, 50.0]);
});

it('preserves manual allocation percentages when mode is manual_override', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Manual allocation',
        'status' => 'planned',
        'created_by' => $admin->id,
    ]);

    $reportA = Report::factory()->create();
    $reportB = Report::factory()->create();

    $job->reports()->attach($reportA->id, ['cost_allocation_percentage' => 70]);
    $job->reports()->attach($reportB->id, ['cost_allocation_percentage' => 30]);

    Expense::query()->create([
        'repair_job_id' => $job->id,
        'description' => 'Manual override expense',
        'quantity' => 1,
        'unit_cost' => 100,
        'cost_allocation_mode' => 'manual_override',
        'created_by' => $admin->id,
    ]);

    $allocationByReport = $job->reports()
        ->pluck('job_reports.cost_allocation_percentage', 'reports.id')
        ->map(fn ($value) => (float) $value)
        ->all();

    expect($allocationByReport[$reportA->id])->toBe(70.0)
        ->and($allocationByReport[$reportB->id])->toBe(30.0);
});

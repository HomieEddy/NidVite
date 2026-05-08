<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StagingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds observability-ready demo data for map and analytics', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(StagingDemoSeeder::class);

    $response = $this->getJson(route('api.reports.geojson'));

    $response->assertOk();
    expect($response->json('features'))->toHaveCount(30);

    $repairedResponse = $this->getJson(route('api.reports.geojson', ['status' => 'repaired']));
    $repairedResponse->assertOk();
    expect($repairedResponse->json('features'))->toHaveCount(10);

    $start = now()->subDays(30)->startOfDay();
    $end = now()->endOfDay();

    $completedJobs = DB::table('repair_jobs')
        ->where('status', 'completed')
        ->whereBetween('completed_at', [$start, $end])
        ->count();

    expect($completedJobs)->toBe(10);

    $neighborhoodCostRows = DB::table('expenses')
        ->join('repair_jobs', 'repair_jobs.id', '=', 'expenses.repair_job_id')
        ->join('job_reports', 'job_reports.repair_job_id', '=', 'repair_jobs.id')
        ->join('reports', 'reports.id', '=', 'job_reports.report_id')
        ->whereNotNull('reports.neighborhood')
        ->where('reports.neighborhood', '!=', '')
        ->whereBetween('expenses.created_at', [$start, $end])
        ->selectRaw('reports.neighborhood as neighborhood, SUM(expenses.total * (job_reports.cost_allocation_percentage / 100.0)) as total_cost')
        ->groupBy('reports.neighborhood')
        ->get();

    expect($neighborhoodCostRows)->not->toBeEmpty();
});

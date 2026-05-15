<?php

use App\Actions\RepairJobs\SyncReportsToJobStatusAction;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('maps job planned status to report scheduled status', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'planned',
        'created_by' => $admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    app(SyncReportsToJobStatusAction::class)->execute($job->status, [$report->id]);

    $report->refresh();
    expect($report->status)->toBe('scheduled');
});

it('maps job in_progress status to report in_progress status', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'in_progress',
        'created_by' => $admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    app(SyncReportsToJobStatusAction::class)->execute('planned', [$report->id]);
    app(SyncReportsToJobStatusAction::class)->execute($job->status, [$report->id]);

    $report->refresh();
    expect($report->status)->toBe('in_progress');
});

it('maps job completed status to report repaired status', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'completed',
        'created_by' => $admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    app(SyncReportsToJobStatusAction::class)->execute('planned', [$report->id]);
    app(SyncReportsToJobStatusAction::class)->execute('in_progress', [$report->id]);
    app(SyncReportsToJobStatusAction::class)->execute($job->status, [$report->id]);

    $report->refresh();
    expect($report->status)->toBe('repaired');
});

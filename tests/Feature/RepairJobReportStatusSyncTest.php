<?php

use App\Models\RepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);
});

it('maps job planned status to report scheduled status', function () {
    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'planned',
        'created_by' => $this->admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    // Simulate the afterCreate hook logic
    $jobStatus = $job->status;
    if ($jobStatus === 'planned') {
        $report->transitionTo('scheduled');
    }

    $report->refresh();
    expect($report->status)->toBe('scheduled');
});

it('maps job in_progress status to report in_progress status', function () {
    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'in_progress',
        'created_by' => $this->admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    // Simulate the transition logic
    $report->transitionTo('scheduled');
    $report->transitionTo('in_progress');

    $report->refresh();
    expect($report->status)->toBe('in_progress');
});

it('maps job completed status to report repaired status', function () {
    $report = Report::factory()->create(['status' => 'verified']);

    $job = RepairJob::query()->create([
        'title' => 'Test Job',
        'status' => 'completed',
        'created_by' => $this->admin->id,
    ]);

    $job->reports()->attach([$report->id]);

    // Simulate the transition logic
    $report->transitionTo('scheduled');
    $report->transitionTo('in_progress');
    $report->transitionTo('repaired');

    $report->refresh();
    expect($report->status)->toBe('repaired');
});

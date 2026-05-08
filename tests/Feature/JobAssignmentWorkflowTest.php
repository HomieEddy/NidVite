<?php

use App\Models\RepairJob;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows manager to assign multiple active service workers to a job', function () {
    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);

    $workerA = User::factory()->create([
        'role_id' => Role::where('slug', 'service_worker')->value('id'),
        'is_active' => true,
    ]);

    $workerB = User::factory()->create([
        'role_id' => Role::where('slug', 'service_worker')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Job assignment test',
        'status' => 'planned',
        'created_by' => $manager->id,
    ]);

    $job->assignWorkers([$workerA->id, $workerB->id]);

    expect($job->users()->count())->toBe(2)
        ->and($job->users()->whereKey($workerA->id)->exists())->toBeTrue()
        ->and($job->users()->whereKey($workerB->id)->exists())->toBeTrue();
});

it('allows service worker to self-assign eligible jobs', function () {
    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);

    $worker = User::factory()->create([
        'role_id' => Role::where('slug', 'service_worker')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Self assign test',
        'status' => 'in_progress',
        'created_by' => $manager->id,
    ]);

    $job->selfAssign($worker);

    expect($job->users()->whereKey($worker->id)->exists())->toBeTrue();
});

it('prevents self-assignment when job is not eligible', function () {
    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);

    $worker = User::factory()->create([
        'role_id' => Role::where('slug', 'service_worker')->value('id'),
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Self assign guard',
        'status' => 'completed',
        'created_by' => $manager->id,
    ]);

    expect(fn () => $job->selfAssign($worker))
        ->toThrow(InvalidArgumentException::class, 'This job is not eligible for self-assignment.');

    expect($job->users()->whereKey($worker->id)->exists())->toBeFalse();
});

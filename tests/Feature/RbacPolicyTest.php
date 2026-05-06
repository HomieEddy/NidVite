<?php

use App\Models\Expense;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
    $this->seed(ExpenseCategorySeeder::class);
    $this->roles = Role::all()->keyBy('slug');
});

describe('ReportPolicy', function () {
    it('allows all roles to view any report', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('viewAny', Report::class))->toBeTrue();
    })->with(['admin', 'manager', 'service_worker', 'accountant', 'viewer']);

    it('allows all roles to view a report', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        $report = Report::factory()->make();
        expect($user->can('view', $report))->toBeTrue();
    })->with(['admin', 'manager', 'service_worker', 'accountant', 'viewer']);

    it('allows admin and manager to create reports', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', Report::class))->toBeTrue();
    })->with(['admin', 'manager']);

    it('denies create reports to non-manager roles', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', Report::class))->toBeFalse();
    })->with(['service_worker', 'accountant', 'viewer']);

    it('allows admin and manager to update reports', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        $report = Report::factory()->make();
        expect($user->can('update', $report))->toBeTrue();
    })->with(['admin', 'manager']);

    it('denies update reports to non-manager roles', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        $report = Report::factory()->make();
        expect($user->can('update', $report))->toBeFalse();
    })->with(['service_worker', 'accountant', 'viewer']);

    it('allows only admin to delete reports', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $manager = User::factory()->create(['role_id' => $this->roles['manager']->id]);
        $report = Report::factory()->make();

        expect($admin->can('delete', $report))->toBeTrue()
            ->and($manager->can('delete', $report))->toBeFalse();
    });
});

describe('UserPolicy', function () {
    it('allows only admin to view any user', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $manager = User::factory()->create(['role_id' => $this->roles['manager']->id]);

        expect($admin->can('viewAny', User::class))->toBeTrue()
            ->and($manager->can('viewAny', User::class))->toBeFalse();
    });

    it('allows only admin to create users', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $viewer = User::factory()->create(['role_id' => $this->roles['viewer']->id]);

        expect($admin->can('create', User::class))->toBeTrue()
            ->and($viewer->can('create', User::class))->toBeFalse();
    });

    it('prevents admin from deleting themselves', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);

        expect($admin->can('delete', $admin))->toBeFalse();
    });
});

describe('RepairJobPolicy', function () {
    it('allows all roles to view any repair job', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('viewAny', RepairJob::class))->toBeTrue();
    })->with(['admin', 'manager', 'service_worker', 'accountant', 'viewer']);

    it('allows admin, manager, and service worker to create repair jobs', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', RepairJob::class))->toBeTrue();
    })->with(['admin', 'manager', 'service_worker']);

    it('denies create repair jobs to accountant and viewer', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', RepairJob::class))->toBeFalse();
    })->with(['accountant', 'viewer']);

    it('allows only admin to delete repair jobs', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $manager = User::factory()->create(['role_id' => $this->roles['manager']->id]);
        $repairJob = RepairJob::factory()->make(['created_by' => $admin->id]);

        expect($admin->can('delete', $repairJob))->toBeTrue()
            ->and($manager->can('delete', $repairJob))->toBeFalse();
    });
});

describe('ExpensePolicy', function () {
    it('allows all roles to view any expense', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('viewAny', Expense::class))->toBeTrue();
    })->with(['admin', 'manager', 'service_worker', 'accountant', 'viewer']);

    it('allows admin and accountant to create expenses', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', Expense::class))->toBeTrue();
    })->with(['admin', 'accountant']);

    it('denies create expenses to non-accountant roles', function (string $roleSlug) {
        $user = User::factory()->create(['role_id' => $this->roles[$roleSlug]->id]);
        expect($user->can('create', Expense::class))->toBeFalse();
    })->with(['manager', 'service_worker', 'viewer']);

    it('allows only admin to delete expenses', function () {
        $admin = User::factory()->create(['role_id' => $this->roles['admin']->id]);
        $accountant = User::factory()->create(['role_id' => $this->roles['accountant']->id]);
        $expense = Expense::factory()->make([
            'repair_job_id' => 1,
            'category_id' => $this->roles['accountant']->id,
            'created_by' => $admin->id,
        ]);

        expect($admin->can('delete', $expense))->toBeTrue()
            ->and($accountant->can('delete', $expense))->toBeFalse();
    });
});

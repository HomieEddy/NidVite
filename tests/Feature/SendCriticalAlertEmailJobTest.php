<?php

use App\Jobs\SendCriticalAlertEmailJob;
use App\Models\EmailDeliveryLog;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});

it('marks log as delivered on successful send', function () {
    Notification::fake();

    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $report = Report::factory()->create(['priority' => 'critical']);

    $log = EmailDeliveryLog::query()->create([
        'report_id' => $report->id,
        'user_id' => $admin->id,
        'kind' => 'critical_alert',
        'status' => 'pending',
        'attempts' => 0,
    ]);

    (new SendCriticalAlertEmailJob($report->id, $admin->id, $log->id))->handle();

    $log->refresh();

    expect($log->status)->toBe('delivered');
    expect($log->attempts)->toBe(1);
    expect($log->delivered_at)->not->toBeNull();
});

it('marks log as permanent_failed after retries are exhausted', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $report = Report::factory()->create(['priority' => 'critical']);

    $log = EmailDeliveryLog::query()->create([
        'report_id' => $report->id,
        'user_id' => $admin->id,
        'kind' => 'critical_alert',
        'status' => 'pending',
        'attempts' => 3,
    ]);

    $job = new SendCriticalAlertEmailJob($report->id, $admin->id, $log->id);
    $job->failed(new \RuntimeException('simulated bounce'));

    $log->refresh();

    expect($log->status)->toBe('permanent_failed');
    expect($log->failed_at)->not->toBeNull();
    expect($log->last_error)->toContain('simulated bounce');
});

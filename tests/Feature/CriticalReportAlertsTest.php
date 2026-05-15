<?php

use App\Events\ReportCreated;
use App\Jobs\SendCriticalAlertEmailJob;
use App\Listeners\SendCriticalReportAlerts;
use App\Models\EmailDeliveryLog;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});

it('queues critical alerts for admin and manager only', function () {
    Queue::fake();

    $admin = User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);
    $manager = User::factory()->create(['role_id' => Role::where('slug', 'manager')->value('id'), 'is_active' => true]);
    $viewer = User::factory()->create(['role_id' => Role::where('slug', 'viewer')->value('id'), 'is_active' => true]);

    $report = Report::factory()->create(['priority' => 'critical']);

    app(SendCriticalReportAlerts::class)->handle(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->pluck('user_id')->sort()->values()->all())
        ->toBe([$admin->id, $manager->id]);

    expect(EmailDeliveryLog::query()->pluck('user_id')->all())
        ->not->toContain($viewer->id);

    Queue::assertPushed(SendCriticalAlertEmailJob::class, function (SendCriticalAlertEmailJob $job): bool {
        return $job->afterCommit === true;
    });
    Queue::assertPushed(SendCriticalAlertEmailJob::class, 2);

    $logIds = EmailDeliveryLog::query()->pluck('id')->all();

    Queue::assertPushed(SendCriticalAlertEmailJob::class, function (SendCriticalAlertEmailJob $job) use ($logIds): bool {
        return in_array($job->deliveryLogId, $logIds, true)
            && in_array($job->userId, EmailDeliveryLog::query()->pluck('user_id')->all(), true);
    });
});

it('skips inactive recipients even when their role is eligible', function () {
    Queue::fake();

    User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);
    User::factory()->create(['role_id' => Role::where('slug', 'manager')->value('id'), 'is_active' => false]);

    $report = Report::factory()->create(['priority' => 'critical']);

    app(SendCriticalReportAlerts::class)->handle(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->count())->toBe(1);
    Queue::assertPushed(SendCriticalAlertEmailJob::class, 1);
});

it('does not duplicate logs or jobs when alert event is handled twice', function () {
    Queue::fake();

    User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);
    User::factory()->create(['role_id' => Role::where('slug', 'manager')->value('id'), 'is_active' => true]);

    $report = Report::factory()->create(['priority' => 'critical']);

    app(SendCriticalReportAlerts::class)->handle(new ReportCreated($report));
    app(SendCriticalReportAlerts::class)->handle(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->count())->toBe(2);
    Queue::assertPushed(SendCriticalAlertEmailJob::class, 2);
});

it('does not queue critical alerts for non-critical priorities', function () {
    Queue::fake();

    User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);

    $report = Report::factory()->create(['priority' => 'normal']);

    app(SendCriticalReportAlerts::class)->handle(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

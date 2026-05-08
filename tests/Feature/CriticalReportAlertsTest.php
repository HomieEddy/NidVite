<?php

use App\Events\ReportCreated;
use App\Jobs\SendCriticalAlertEmailJob;
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

    User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);
    User::factory()->create(['role_id' => Role::where('slug', 'manager')->value('id'), 'is_active' => true]);
    User::factory()->create(['role_id' => Role::where('slug', 'viewer')->value('id'), 'is_active' => true]);

    $report = Report::factory()->create(['priority' => 'critical']);

    event(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->count())->toBe(2);

    Queue::assertPushed(SendCriticalAlertEmailJob::class, 2);
});

it('does not queue critical alerts for non-critical priorities', function () {
    Queue::fake();

    User::factory()->create(['role_id' => Role::where('slug', 'admin')->value('id'), 'is_active' => true]);

    $report = Report::factory()->create(['priority' => 'normal']);

    event(new ReportCreated($report));

    expect(EmailDeliveryLog::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

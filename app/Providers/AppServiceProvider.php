<?php

namespace App\Providers;

use App\Health\Checks\MailConfigurationCheck;
use App\Listeners\EnforceAdminConcurrentSessionLimit;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, EnforceAdminConcurrentSessionLimit::class);

        Health::checks([
            DatabaseCheck::new()->connectionName(config('database.default', 'pgsql')),
            RedisCheck::new()->connectionName(config('database.redis.default.connection', 'default')),
            QueueCheck::new()
                ->onQueue(config('queue.connections.redis.queue', 'default'))
                ->failWhenHealthJobTakesLongerThanMinutes((int) env('HEALTH_QUEUE_MAX_AGE_MINUTES', 5)),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage((int) env('HEALTH_DISK_WARN_PERCENT', 80))
                ->failWhenUsedSpaceIsAbovePercentage((int) env('HEALTH_DISK_FAIL_PERCENT', 90)),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes((int) env('HEALTH_SCHEDULE_MAX_AGE_MINUTES', 2)),
            MailConfigurationCheck::new(),
        ]);
    }
}

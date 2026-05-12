<?php

use App\Mail\WeeklyOperationsDigest;
use App\Models\Report;
use App\Services\Reports\ReliabilityScoreService;
use App\Services\Reports\WeeklyOperationsDigestService;
use Bepsvpt\SecureHeaders\SecureHeaders;
use Database\Seeders\StagingDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('ops:opcache-clear', function () {
    if (! function_exists('opcache_reset')) {
        $this->warn('OPcache extension is not enabled for this PHP runtime.');

        return 0;
    }

    $result = opcache_reset();

    if ($result) {
        $this->info('OPcache reset completed successfully.');

        return 0;
    }

    $this->error('OPcache reset failed. Check PHP OPcache configuration.');

    return 1;
})->purpose('Clear PHP OPcache after deployment');

Artisan::command('security:check-headers', function () {
    $issues = [];

    $headers = (new SecureHeaders(config('secure-headers', [])))->headers();

    $requiredHeaders = [
        'Content-Security-Policy',
        'Strict-Transport-Security',
        'X-Frame-Options',
        'X-Content-Type-Options',
    ];

    foreach ($requiredHeaders as $header) {
        if (! isset($headers[$header]) || $headers[$header] === '') {
            $issues[] = "Missing {$header} header.";
        }
    }

    if (config('secure-headers.csp.enable') !== true) {
        $issues[] = 'secure-headers.csp.enable must be true.';
    }

    if (config('secure-headers.hsts.enable') !== true) {
        $issues[] = 'secure-headers.hsts.enable must be true.';
    }

    if (config('secure-headers.x-content-type-options') !== 'nosniff') {
        $issues[] = 'secure-headers.x-content-type-options must be nosniff.';
    }

    $frameOptions = strtolower((string) config('secure-headers.x-frame-options'));

    if (! in_array($frameOptions, ['deny', 'sameorigin'], true)) {
        $issues[] = 'secure-headers.x-frame-options must be deny or sameorigin.';
    }

    if ($issues !== []) {
        foreach ($issues as $issue) {
            $this->error($issue);
        }

        return 1;
    }

    $this->info('Secure headers configuration check passed.');

    return 0;
})->purpose('Fail-fast validation for secure headers configuration in CI');

Artisan::command('ops:check-staging-readiness', function () {
    $issues = [];

    if (! app()->environment(['staging', 'testing'])) {
        $issues[] = 'APP_ENV must be staging for staging readiness checks.';
    }

    if ((string) config('queue.default') !== 'redis') {
        $issues[] = 'QUEUE_CONNECTION must resolve to redis.';
    }

    if ((string) config('cache.default') !== 'redis') {
        $issues[] = 'CACHE_STORE must resolve to redis.';
    }

    if ((string) config('filesystems.default') !== 'r2') {
        $issues[] = 'FILESYSTEM_DISK must resolve to r2.';
    }

    try {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            $issues[] = 'DB_CONNECTION must resolve to pgsql.';
        } else {
            $postgis = DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis') AS installed");

            if (! isset($postgis->installed) || ! (bool) $postgis->installed) {
                $issues[] = 'PostGIS extension is not installed on the active database.';
            }
        }
    } catch (Throwable $exception) {
        $issues[] = 'Database/PostGIS readiness check failed: '.$exception->getMessage();
    }

    $mailer = (string) config('mail.default');

    if (! in_array($mailer, ['log', 'array'], true)) {
        $issues[] = 'MAIL_MAILER must be log or array in staging to prevent outbound delivery.';
    }

    $workerScript = base_path('deploy/railway/worker.sh');
    $schedulerScript = base_path('deploy/railway/scheduler.sh');

    if (! is_file($workerScript) || ! str_contains((string) file_get_contents($workerScript), 'queue:work redis')) {
        $issues[] = 'Railway worker script must run queue:work redis.';
    }

    if (! is_file($schedulerScript) || ! str_contains((string) file_get_contents($schedulerScript), 'schedule:run')) {
        $issues[] = 'Railway scheduler script must run schedule:run.';
    }

    if ($issues !== []) {
        foreach ($issues as $issue) {
            $this->error($issue);
        }

        return 1;
    }

    $this->info('Staging readiness check passed.');

    return 0;
})->purpose('Fail-fast validation for Railway staging parity and safety checks');

Artisan::command('ops:seed-staging-demo {--fresh : Recreate schema before seeding}', function () {
    if (! app()->environment(['staging', 'testing'])) {
        $this->error('This command is restricted to staging/testing environments.');

        return 1;
    }

    if ((bool) $this->option('fresh')) {
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    Artisan::call('db:seed', ['--class' => StagingDemoSeeder::class, '--force' => true]);

    $this->line(Artisan::output());
    $this->info('Staging demo seed completed.');

    return 0;
})->purpose('Reset and seed staging demo data safely');

Artisan::command('reports:run-retention', function () {
    $ipRetentionDays = (int) config('retention.ip_purge_days', 30);
    $archiveRetentionDays = (int) config('retention.report_archive_days', 730);
    $coldStorageDisk = (string) config('retention.cold_storage_disk', 'r2-cold');
    $coldStoragePrefix = trim((string) config('retention.cold_storage_prefix', 'cold/reports'), '/');

    $ipCutoff = now()->subDays($ipRetentionDays);
    $archiveCutoff = now()->subDays($archiveRetentionDays);

    $purgedIpRows = Report::query()
        ->whereNotNull('ip_address_raw')
        ->where('created_at', '<', $ipCutoff)
        ->update(['ip_address_raw' => null]);

    $archivedCount = 0;
    $archiveErrors = 0;

    Report::query()
        ->whereNull('archived_at')
        ->where('created_at', '<', $archiveCutoff)
        ->orderBy('id')
        ->chunkById(100, function ($reports) use (&$archivedCount, &$archiveErrors, $coldStorageDisk, $coldStoragePrefix) {
            foreach ($reports as $report) {
                try {
                    DB::transaction(function () use ($report, &$archivedCount, $coldStorageDisk, $coldStoragePrefix) {
                        $year = $report->created_at?->format('Y') ?? 'unknown';
                        $month = $report->created_at?->format('m') ?? '00';
                        $path = "{$coldStoragePrefix}/{$year}/{$month}/report-{$report->uuid}.json";

                        $payload = [
                            'version' => 1,
                            'archived_at' => now()->toIso8601String(),
                            'report' => [
                                'uuid' => $report->uuid,
                                'reporter_email' => $report->reporter_email,
                                'preferred_locale' => $report->preferred_locale,
                                'status' => $report->status,
                                'priority' => $report->priority,
                                'category_id' => $report->category_id,
                                'description' => $report->description,
                                'address' => $report->address,
                                'neighborhood' => $report->neighborhood,
                                'borough' => $report->borough,
                                'created_at' => $report->created_at?->toIso8601String(),
                                'updated_at' => $report->updated_at?->toIso8601String(),
                                'completed_at' => $report->completed_at?->toIso8601String(),
                            ],
                        ];

                        Storage::disk($coldStorageDisk)->put(
                            $path,
                            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                        );

                        $report->forceFill([
                            'ip_address_raw' => null,
                            'archive_path' => $path,
                            'archived_at' => now(),
                        ])->save();
                        $archivedCount++;
                    });
                } catch (Throwable $exception) {
                    report($exception);
                    $archiveErrors++;
                }
            }
        });

    $this->info("Purged raw IPs: {$purgedIpRows}");
    $this->info("Archived reports: {$archivedCount}");

    if ($archiveErrors > 0) {
        $this->error("Archive errors: {$archiveErrors}");

        return 1;
    }

    return 0;
})->purpose('Purge raw IPs and archive old reports to cold storage');

Artisan::command('reports:recompute-reliability', function () {
    $scorer = app(ReliabilityScoreService::class);
    $updated = 0;

    Report::query()
        ->orderBy('id')
        ->chunkById(200, function ($reports) use ($scorer, &$updated): void {
            foreach ($reports as $report) {
                $snapshot = $scorer->score($report);

                $report->forceFill([
                    'reliability_score' => $snapshot['score'],
                    'reliability_breakdown' => $snapshot['breakdown'],
                    'reliability_scored_at' => now(),
                ])->saveQuietly();

                $updated++;
            }
        });

    $this->info("Recomputed reliability for {$updated} reports.");

    return 0;
})->purpose('Recompute reliability score snapshots for all reports');

Artisan::command('reports:send-weekly-digest', function () {
    $recipients = collect((array) config('operations_digest.recipients', []))
        ->map(fn ($email): string => trim((string) $email))
        ->filter(fn (string $email): bool => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
        ->unique()
        ->values();

    if ($recipients->isEmpty()) {
        $this->info('No digest recipients configured; skipping weekly digest send.');

        return 0;
    }

    $locale = (string) config('operations_digest.locale', 'fr');
    $summary = app(WeeklyOperationsDigestService::class)->buildSummary(now());

    foreach ($recipients as $recipient) {
        Mail::to($recipient)->queue(new WeeklyOperationsDigest($summary, $locale));
    }

    $this->info('Weekly operations digest queued for '.$recipients->count().' recipients.');

    return 0;
})->purpose('Send weekly operations digest to configured recipients');

Schedule::command('health:schedule-check-heartbeat')
    ->everyMinute()
    ->monitorName('health:schedule-check-heartbeat')
    ->withoutOverlapping();

Schedule::command('health:queue-check-heartbeat')
    ->everyMinute()
    ->monitorName('health:queue-check-heartbeat')
    ->withoutOverlapping();

Schedule::command('health:check --fail-command-on-failing-check')
    ->everyFiveMinutes()
    ->monitorName('health:check')
    ->withoutOverlapping();

Schedule::command('backup:clean')
    ->daily()
    ->at('01:00')
    ->monitorName('backup:clean')
    ->withoutOverlapping();

Schedule::command('backup:run --only-db')
    ->daily()
    ->at('01:30')
    ->monitorName('backup:run-db')
    ->withoutOverlapping();

Schedule::command('reports:run-retention')
    ->daily()
    ->at('02:30')
    ->monitorName('reports:run-retention')
    ->withoutOverlapping();

Schedule::command('reports:send-weekly-digest')
    ->weeklyOn(
        (int) config('operations_digest.day_of_week', 1),
        (string) config('operations_digest.time', '08:00')
    )
    ->monitorName('reports:send-weekly-digest')
    ->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [MonitoredScheduledTaskLogItem::class]])
    ->daily()
    ->at('03:30')
    ->monitorName('schedule-monitor:prune')
    ->withoutOverlapping();

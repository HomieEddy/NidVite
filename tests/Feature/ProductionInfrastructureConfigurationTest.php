<?php

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('configures an r2 disk for private object storage', function () {
    $r2 = config('filesystems.disks.r2');

    expect($r2)->toBeArray();
    expect($r2['driver'] ?? null)->toBe('s3');
    expect($r2['visibility'] ?? null)->toBe('private');
    expect(config('media-library.disk_name'))->toBe(env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')));
});

it('keeps redis as the queue default fallback', function () {
    $queueConfig = file_get_contents(config_path('queue.php'));

    expect($queueConfig)->toContain("'default' => env('QUEUE_CONNECTION', 'redis')");
});

it('configures backups to use r2 by default', function () {
    expect(config('backup.backup.destination.disks'))->toContain('r2');
    expect(config('backup.monitor_backups.0.disks'))->toContain('r2');
});

it('registers a health check json endpoint route', function () {
    $route = app('router')->getRoutes()->getByName('health.json');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('health');
});

it('registers a signed media proxy route', function () {
    $route = app('router')->getRoutes()->getByName('media.signed');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('signed');
});

it('generates valid signed report photo URLs', function () {
    $report = new class extends Report
    {
        public function getMedia(string $collectionName = 'default', array|callable $filters = []): MediaCollection
        {
            $media = new Media;
            $media->id = 123;

            return new MediaCollection([
                $media,
            ]);
        }
    };

    $url = $report->signedPhotoUrls(10)[0];

    expect($url)->toContain('/media/123');
    expect(URL::hasValidSignature(Request::create($url)))->toBeTrue();
});

it('provides dedicated railway command scripts for web worker and scheduler', function () {
    expect(base_path('deploy/railway/web.sh'))->toBeFile();
    expect(base_path('deploy/railway/worker.sh'))->toBeFile();
    expect(base_path('deploy/railway/scheduler.sh'))->toBeFile();
    expect(base_path('deploy/railway/staging-readiness.sh'))->toBeFile();

    expect(file_get_contents(base_path('railway.toml')))->toContain('sh deploy/railway/web.sh');
    expect(file_get_contents(base_path('deploy/railway/worker.sh')))->toContain('queue:work redis');
    expect(file_get_contents(base_path('deploy/railway/scheduler.sh')))->toContain('schedule:run');
    expect(file_get_contents(base_path('deploy/railway/scheduler.sh')))->toContain('schedule-monitor:sync');
    expect(file_get_contents(base_path('deploy/railway/staging-readiness.sh')))->toContain('ops:check-staging-readiness');
});

it('schedules backup monitoring and retention commands', function () {
    $consoleRoutes = file_get_contents(base_path('routes/console.php'));

    expect($consoleRoutes)->toContain("Schedule::command('backup:run --only-db')");
    expect($consoleRoutes)->toContain("Schedule::command('backup:clean')");
    expect($consoleRoutes)->toContain("Schedule::command('health:check --fail-command-on-failing-check')");
    expect($consoleRoutes)->toContain("Schedule::command('health:schedule-check-heartbeat')");
    expect($consoleRoutes)->toContain("Schedule::command('health:queue-check-heartbeat')");
    expect($consoleRoutes)->toContain("Artisan::command('reports:run-retention'");
    expect($consoleRoutes)->toContain("->monitorName('reports:run-retention')");
});

it('defines retention config defaults', function () {
    expect(config('retention.ip_purge_days'))->toBe(30);
    expect(config('retention.report_archive_days'))->toBe(730);
    expect(config('retention.cold_storage_disk'))->toBe('r2-cold');
});

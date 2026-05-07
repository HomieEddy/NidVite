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

    expect(file_get_contents(base_path('railway.toml')))->toContain('sh deploy/railway/web.sh');
    expect(file_get_contents(base_path('deploy/railway/worker.sh')))->toContain('queue:work redis');
    expect(file_get_contents(base_path('deploy/railway/scheduler.sh')))->toContain('schedule:run');
});

<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use Spatie\ResponseCache\Facades\ResponseCache;

class InvalidatePublicResponseCache
{
    public function handle(ReportCreated $event): void
    {
        ResponseCache::forget([
            '/',
            '/carte',
        ]);
    }
}

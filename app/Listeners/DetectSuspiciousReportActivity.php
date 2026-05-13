<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Services\SuspiciousActivityDetector;
use Illuminate\Support\Facades\Log;

class DetectSuspiciousReportActivity
{
    public function __construct(
        private readonly SuspiciousActivityDetector $detector
    ) {}

    public function handle(ReportCreated $event): void
    {
        try {
            $this->detector->detect($event->report);
        } catch (\Throwable $exception) {
            Log::warning('Suspicious activity detection failed; report creation flow continued.', [
                'report_id' => $event->report->getKey(),
                'exception_class' => $exception::class,
            ]);
        }
    }
}

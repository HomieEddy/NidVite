<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Services\SuspiciousActivityDetector;

class DetectSuspiciousReportActivity
{
    public function __construct(
        private readonly SuspiciousActivityDetector $detector
    ) {}

    public function handle(ReportCreated $event): void
    {
        $this->detector->detect($event->report);
    }
}

<?php

namespace App\Actions\Reports;

use App\Models\Report;

class TransitionReportStatusAction
{
    public function __invoke(Report $report, string $newStatus, ?string $reason = null): void
    {
        $report->transitionTo($newStatus, $reason);
    }
}

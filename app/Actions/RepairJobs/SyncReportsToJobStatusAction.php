<?php

namespace App\Actions\RepairJobs;

use App\Models\Report;
use App\Services\RepairJobs\RepairJobStatusMapper;
use InvalidArgumentException;

class SyncReportsToJobStatusAction
{
    /**
     * Sync all associated reports to the job's status.
     *
     * @param  array<int|string>  $reportIds
     */
    public function execute(string $jobStatus, array $reportIds): void
    {
        if (empty($reportIds)) {
            return;
        }

        $targetReportStatus = RepairJobStatusMapper::mapJobStatusToReportStatus($jobStatus);

        if ($targetReportStatus === null) {
            return;
        }

        foreach ($reportIds as $reportId) {
            $report = Report::query()->findOrFail($reportId);
            try {
                $report->transitionTo($targetReportStatus);
            } catch (InvalidArgumentException $e) {
                report($e);
            }
        }
    }
}

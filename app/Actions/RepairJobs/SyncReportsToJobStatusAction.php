<?php

namespace App\Actions\RepairJobs;

use App\Models\Report;
use App\Services\RepairJobs\RepairJobStatusMapper;
use InvalidArgumentException;

class SyncReportsToJobStatusAction
{
    /**
     * Synchronize the statuses of all associated reports to match the given repair job status.
     *
     * This method maps the provided repair job status to the appropriate report status using
     * RepairJobStatusMapper, and then transitions each report in the provided list of IDs to
     * the mapped status. If a report cannot be transitioned (e.g., invalid state), the exception
     * is caught and reported, but processing continues for other reports.
     *
     * @param  string  $jobStatus  The status of the repair job (e.g., 'planned', 'in_progress', 'completed').
     * @param  array<int|string>  $reportIds  The IDs of the reports to synchronize.
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

<?php

namespace App\Services\RepairJobs;

class RepairJobStatusMapper
{
    /**
     * Map repair job status to corresponding report status.
     *
     * @return string|null The target report status, or null if no mapping exists
     */
    public static function mapJobStatusToReportStatus(string $jobStatus): ?string
    {
        return match ($jobStatus) {
            'planned' => 'scheduled',
            'in_progress' => 'in_progress',
            'completed' => 'repaired',
            default => null,
        };
    }
}

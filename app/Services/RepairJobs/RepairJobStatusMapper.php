<?php

namespace App\Services\RepairJobs;

class RepairJobStatusMapper
{
    /**
     * Map a repair job status to the corresponding report status.
     *
     * This static method provides a central mapping between repair job statuses and
     * their associated report statuses for synchronization logic. Returns null if
     * the job status does not have a corresponding report status.
     *
     * @param  string  $jobStatus  The status of the repair job (e.g., 'planned', 'in_progress', 'completed').
     * @return string|null The mapped report status, or null if no mapping exists.
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

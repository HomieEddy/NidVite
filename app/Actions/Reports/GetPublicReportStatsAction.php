<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\Report;

class GetPublicReportStatsAction
{
    /**
     * @return array{totalReported: int, totalFixed: int, totalPending: int, velocity: string}
     */
    public function __invoke(string $locale): array
    {
        $pendingStatuses = [
            ReportStatus::Received->value,
            ReportStatus::Verified->value,
            ReportStatus::Scheduled->value,
            ReportStatus::InProgress->value,
        ];

        $stats = Report::query()
            ->where('is_spam', false)
            ->where('status', '!=', ReportStatus::Rejected->value)
            ->whereNotNull('location')
            ->selectRaw(
                'COUNT(*) AS total_reported,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS total_fixed,
                SUM(CASE WHEN status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS total_pending,
                AVG(CASE WHEN status = ? AND completed_at IS NOT NULL AND created_at IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (completed_at - created_at)) / 86400 END) AS avg_days',
                [
                    ReportStatus::Repaired->value,
                    ...$pendingStatuses,
                    ReportStatus::Repaired->value,
                ]
            )
            ->first();

        $totalReported = (int) ($stats?->total_reported ?? 0);
        $totalFixed = (int) ($stats?->total_fixed ?? 0);
        $totalPending = (int) ($stats?->total_pending ?? 0);
        $avgDays = $stats?->avg_days;

        $velocity = null;
        if ($avgDays !== null) {
            $velocity = round((float) $avgDays, 1);
        }

        return [
            'totalReported' => $totalReported,
            'totalFixed' => $totalFixed,
            'totalPending' => $totalPending,
            'velocity' => $velocity !== null
                ? __('report.velocity_days', ['count' => $velocity], $locale)
                : __('report.velocity_na', [], $locale),
        ];
    }
}

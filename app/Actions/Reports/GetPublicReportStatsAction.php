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
        $visibleReports = Report::query()
            ->where('is_spam', false)
            ->where('status', '!=', ReportStatus::Rejected->value)
            ->whereNotNull('location');

        $totalReported = (clone $visibleReports)->count();
        $totalFixed = (clone $visibleReports)->where('status', ReportStatus::Repaired->value)->count();
        $totalPending = (clone $visibleReports)
            ->whereIn('status', [
                ReportStatus::Received->value,
                ReportStatus::Verified->value,
                ReportStatus::Scheduled->value,
                ReportStatus::InProgress->value,
            ])
            ->count();

        $avgDays = (clone $visibleReports)
            ->where('status', ReportStatus::Repaired->value)
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

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

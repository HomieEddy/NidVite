<?php

namespace App\Actions\Reports;

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
            ->where('status', '!=', 'rejected')
            ->whereNotNull('location');

        $totalReported = (clone $visibleReports)->count();
        $totalFixed = (clone $visibleReports)->where('status', 'repaired')->count();
        $totalPending = (clone $visibleReports)->whereIn('status', ['received', 'verified', 'scheduled', 'in_progress'])->count();

        $avgDays = (clone $visibleReports)
            ->where('status', 'repaired')
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
            'velocity' => $locale === 'fr'
                ? ($velocity !== null ? $velocity.' jours' : 'N/D')
                : ($velocity !== null ? $velocity.' days' : 'N/A'),
        ];
    }
}

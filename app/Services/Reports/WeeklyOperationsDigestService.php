<?php

namespace App\Services\Reports;

use App\Models\Report;
use Carbon\CarbonInterface;

class WeeklyOperationsDigestService
{
    private const UNKNOWN_NEIGHBORHOOD_TOKEN = 'UNKNOWN_NEIGHBORHOOD';

    /**
     * @return array<string, mixed>
     */
    public function buildSummary(CarbonInterface $now): array
    {
        $windowDays = max(1, (int) config('operations_digest.window_days', 7));
        $hotspotLimit = max(1, (int) config('operations_digest.hotspot_limit', 5));

        $windowEnd = $now->copy();
        $windowStart = $now->copy()->subDays($windowDays);

        $newCount = Report::query()
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->count();

        $openCount = Report::query()
            ->whereIn('status', ['received', 'verified', 'scheduled', 'in_progress'])
            ->count();

        $resolvedCount = Report::query()
            ->whereIn('status', ['repaired', 'rejected'])
            ->whereBetween('updated_at', [$windowStart, $windowEnd])
            ->count();

        $neighborhoodHotspots = Report::query()
            ->selectRaw('COALESCE(neighborhood, ?) as neighborhood_key, COUNT(*) as report_count', [self::UNKNOWN_NEIGHBORHOOD_TOKEN])
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->groupBy('neighborhood_key')
            ->orderByDesc('report_count')
            ->limit($hotspotLimit)
            ->get()
            ->map(fn ($row): array => [
                'neighborhood' => (string) data_get($row, 'neighborhood_key', self::UNKNOWN_NEIGHBORHOOD_TOKEN),
                'count' => (int) data_get($row, 'report_count', 0),
            ])
            ->values()
            ->all();

        $zoneCounts = [
            'central' => 0,
            'default' => 0,
        ];

        Report::query()
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->select(['id', 'borough'])
            ->orderBy('id')
            ->chunkById(500, function ($reports) use (&$zoneCounts): void {
                foreach ($reports as $report) {
                    $zone = $this->resolveZoneBucket($report->borough);
                    $zoneCounts[$zone] = ($zoneCounts[$zone] ?? 0) + 1;
                }
            });

        $zoneHotspots = collect($zoneCounts)
            ->map(fn (int $count, string $zone): array => ['zone' => $zone, 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'window' => [
                'start' => $windowStart->toDateString(),
                'end' => $windowEnd->toDateString(),
                'days' => $windowDays,
            ],
            'counts' => [
                'new' => $newCount,
                'open' => $openCount,
                'resolved' => $resolvedCount,
            ],
            'hotspots' => [
                'neighborhoods' => $neighborhoodHotspots,
                'zones' => $zoneHotspots,
            ],
        ];
    }

    private function resolveZoneBucket(?string $borough): string
    {
        $normalizedBorough = trim((string) $borough);

        foreach ((array) config('tracking_experience.eta.zone_boroughs', []) as $bucket => $boroughs) {
            if (in_array($normalizedBorough, (array) $boroughs, true)) {
                return (string) $bucket;
            }
        }

        return 'default';
    }
}

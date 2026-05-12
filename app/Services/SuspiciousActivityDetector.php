<?php

namespace App\Services;

use App\Models\Report;
use App\Models\SuspiciousActivity;

class SuspiciousActivityDetector
{
    public function detect(Report $report): void
    {
        $this->detectRapidRepeatSubmission($report);
        $this->detectGeolocationSpoofing($report);
        $this->detectRoadValidationSignals($report);
    }

    private function detectRoadValidationSignals(Report $report): void
    {
        $decision = (string) ($report->road_validation_decision ?? '');
        if ($decision === '') {
            return;
        }

        $distance = $report->road_distance_meters;
        $distanceThreshold = (float) config('report_validation.max_road_distance_meters', 35);
        $nearMissBuffer = (float) config('report_validation.near_miss_buffer_meters', 10);

        if (in_array($decision, ['fail_off_street', 'fail_both'], true)) {
            $this->storeActivity($report, 'road_validation_off_street', 'high', 'Road validation flagged off-street submission', [
                'decision' => $decision,
                'distance_meters' => $distance,
                'distance_threshold_meters' => $distanceThreshold,
                'validation_mode' => $report->road_validation_mode,
            ]);

            if ($report->location_source === 'geocode') {
                $this->storeActivity($report, 'address_coordinate_mismatch', 'medium', 'Address-geocoded location appears off-street and requires review', [
                    'decision' => $decision,
                    'distance_meters' => $distance,
                    'distance_threshold_meters' => $distanceThreshold,
                    'location_source' => $report->location_source,
                ]);
            }
        }

        if (
            $distance !== null
            && $distance > $distanceThreshold
            && $distance <= ($distanceThreshold + $nearMissBuffer)
        ) {
            $this->storeActivity($report, 'road_validation_near_miss', 'medium', 'Road validation distance is near strict threshold', [
                'decision' => $decision,
                'distance_meters' => $distance,
                'distance_threshold_meters' => $distanceThreshold,
                'near_miss_buffer_meters' => $nearMissBuffer,
                'validation_mode' => $report->road_validation_mode,
            ]);
        }
    }

    private function detectRapidRepeatSubmission(Report $report): void
    {
        $windowMinutes = (int) config('activity_intelligence.rapid_repeat.window_minutes', 5);
        $threshold = (int) config('activity_intelligence.rapid_repeat.threshold', 3);

        $fingerprint = $report->device_fingerprint_hash;
        $ipAddress = $report->ip_address_raw;

        if ($fingerprint === null && $ipAddress === null) {
            return;
        }

        $count = Report::query()
            ->whereKeyNot($report->getKey())
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->where(function ($query) use ($fingerprint, $ipAddress): void {
                if ($fingerprint !== null) {
                    $query->orWhere('device_fingerprint_hash', $fingerprint);
                }

                if ($ipAddress !== null) {
                    $query->orWhere('ip_address_raw', $ipAddress);
                }
            })
            ->count();

        if ($count < $threshold) {
            return;
        }

        $this->storeActivity($report, 'rapid_repeat_submission', 'high', 'Rapid repeat submissions detected', [
            'window_minutes' => $windowMinutes,
            'matching_reports_count' => $count,
            'threshold' => $threshold,
            'fingerprint' => $fingerprint,
            'ip_address' => $ipAddress,
        ]);
    }

    private function detectGeolocationSpoofing(Report $report): void
    {
        $fingerprint = $report->device_fingerprint_hash;

        if ($fingerprint === null) {
            return;
        }

        $windowMinutes = (int) config('activity_intelligence.geolocation.window_minutes', 30);
        $maxTravelMinutes = (int) config('activity_intelligence.geolocation.max_travel_minutes', 10);
        $distanceMetersThreshold = (int) config('activity_intelligence.geolocation.min_distance_meters', 5000);

        $previous = Report::query()
            ->whereKeyNot($report->getKey())
            ->where('device_fingerprint_hash', $fingerprint)
            ->whereNotNull('location')
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->latest('created_at')
            ->first();

        if (! $previous instanceof Report || $report->created_at === null || $previous->created_at === null) {
            return;
        }

        $currentPoint = $this->extractPoint($report);
        $previousPoint = $this->extractPoint($previous);

        if ($currentPoint === null || $previousPoint === null) {
            return;
        }

        $distanceMeters = $this->haversineDistance(
            $previousPoint['lat'],
            $previousPoint['lng'],
            $currentPoint['lat'],
            $currentPoint['lng'],
        );

        $minutesBetween = abs($report->created_at->diffInSeconds($previous->created_at)) / 60;

        if ($distanceMeters < $distanceMetersThreshold || $minutesBetween > $maxTravelMinutes) {
            return;
        }

        $this->storeActivity($report, 'geolocation_spoofing', 'critical', 'Device appears to move too far in too little time', [
            'window_minutes' => $windowMinutes,
            'distance_meters' => (int) round($distanceMeters),
            'minutes_between_reports' => round($minutesBetween, 2),
            'distance_threshold_meters' => $distanceMetersThreshold,
            'max_travel_minutes' => $maxTravelMinutes,
            'previous_report_uuid' => $previous->uuid,
        ]);
    }

    private function extractPoint(Report $report): ?array
    {
        $row = Report::query()
            ->whereKey($report->getKey())
            ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude')
            ->first();

        if ($row === null || ! isset($row->latitude, $row->longitude)) {
            return null;
        }

        return [
            'lat' => (float) $row->latitude,
            'lng' => (float) $row->longitude,
        ];
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function storeActivity(Report $report, string $type, string $severity, string $reason, array $metadata): void
    {
        $exists = SuspiciousActivity::query()
            ->where('report_id', $report->getKey())
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return;
        }

        SuspiciousActivity::query()->create([
            'report_id' => $report->getKey(),
            'type' => $type,
            'severity' => $severity,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}

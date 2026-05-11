<?php

namespace App\Services;

use App\Models\MontrealRoad;
use Illuminate\Support\Facades\Log;

class StreetProximityValidationService
{
    /**
     * @return array<string, bool|float|string|null>
     */
    public function validate(float $latitude, float $longitude, ?float $accuracy): array
    {
        $mode = (string) config('report_validation.mode', 'shadow');
        $mode = in_array($mode, ['shadow', 'strict'], true) ? $mode : 'shadow';

        $distanceThreshold = (float) config('report_validation.max_road_distance_meters', 35);
        $accuracyThreshold = (float) config('report_validation.max_location_accuracy_meters', 50);

        try {
            $distance = MontrealRoad::distanceToNearestMeters($latitude, $longitude);
        } catch (\Throwable $exception) {
            Log::warning('Street proximity lookup unavailable; continuing with fallback.', [
                'message' => $exception->getMessage(),
            ]);
            $distance = null;
        }

        $roadDataAvailable = $distance !== null;
        $roadPassed = ! $roadDataAvailable || $distance <= $distanceThreshold;
        $accuracyPassed = $accuracy !== null && $accuracy <= $accuracyThreshold;

        if (! $roadDataAvailable && $accuracyPassed) {
            $decision = 'pass';
            $reason = 'pass';
        } elseif (! $roadDataAvailable) {
            $decision = 'fail_low_accuracy';
            $reason = 'low_accuracy';
        } elseif ($roadPassed && $accuracyPassed) {
            $decision = 'pass';
            $reason = 'pass';
        } elseif (! $roadPassed && ! $accuracyPassed) {
            $decision = 'fail_both';
            $reason = 'off_street_and_low_accuracy';
        } elseif (! $roadPassed) {
            $decision = 'fail_off_street';
            $reason = 'off_street';
        } else {
            $decision = 'fail_low_accuracy';
            $reason = 'low_accuracy';
        }

        return [
            'distance_meters' => $distance,
            'road_passed' => $roadPassed,
            'accuracy_passed' => $accuracyPassed,
            'decision' => $decision,
            'reason' => $reason,
            'mode' => $mode,
            'should_block' => $mode === 'strict' && $decision !== 'pass',
        ];
    }
}

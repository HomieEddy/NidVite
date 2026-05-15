<?php

namespace App\Services\Reports;

use App\Models\Report;

class ReliabilityScoreService
{
    /**
     * @return array{score: int, breakdown: array<string, mixed>}
     */
    public function score(Report $report): array
    {
        $config = config('reliability_scoring', []);
        $base = (int) ($config['base_score'] ?? 50);
        $weights = is_array($config['weights'] ?? null) ? $config['weights'] : [];

        $factors = [
            'road_validation' => $this->roadValidationContribution($report, $weights),
            'geofence' => ($report->geofence_passed ?? false)
                ? (int) ($weights['geofence_pass_bonus'] ?? 15)
                : (int) ($weights['geofence_fail_penalty'] ?? -20),
            'accuracy' => ($report->location_accuracy_passed ?? false)
                ? (int) ($weights['accuracy_pass_bonus'] ?? 10)
                : (int) ($weights['accuracy_fail_penalty'] ?? -15),
            'source' => $this->sourceContribution($report, $weights),
            'spam' => ($report->is_spam ?? false)
                ? (int) ($weights['spam_penalty'] ?? -60)
                : (int) ($weights['not_spam_bonus'] ?? 10),
            'description' => $this->descriptionContribution($report, $weights),
        ];

        $rawScore = $base + array_sum($factors);
        $score = max(0, min(100, $rawScore));

        return [
            'score' => $score,
            'breakdown' => [
                'base_score' => $base,
                'factors' => $factors,
                'raw_score' => $rawScore,
                'normalized_score' => $score,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $weights
     */
    private function roadValidationContribution(Report $report, array $weights): int
    {
        return match ((string) ($report->road_validation_decision ?? '')) {
            'pass' => (int) ($weights['road_pass_bonus'] ?? 20),
            'fail_low_accuracy' => (int) ($weights['road_fail_low_accuracy_penalty'] ?? -12),
            'fail_off_street' => (int) ($weights['road_fail_off_street_penalty'] ?? -20),
            'fail_both' => (int) ($weights['road_fail_both_penalty'] ?? -30),
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $weights
     */
    private function sourceContribution(Report $report, array $weights): int
    {
        return match ((string) ($report->location_source ?? '')) {
            'gps' => (int) ($weights['source_gps_bonus'] ?? 8),
            'geocode' => (int) ($weights['source_geocode_bonus'] ?? 4),
            'manual' => (int) ($weights['source_manual_penalty'] ?? -3),
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $weights
     */
    private function descriptionContribution(Report $report, array $weights): int
    {
        $length = mb_strlen(trim((string) ($report->description ?? '')));

        if ($length >= 60) {
            return (int) ($weights['description_rich_bonus'] ?? 6);
        }

        if ($length >= 20) {
            return (int) ($weights['description_ok_bonus'] ?? 2);
        }

        return (int) ($weights['description_short_penalty'] ?? -5);
    }
}

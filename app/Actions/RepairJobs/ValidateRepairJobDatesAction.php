<?php

namespace App\Actions\RepairJobs;

use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ValidateRepairJobDatesAction
{
    /**
     * Validate repair job date fields to prevent past/future logic conflicts.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string>  $reportIds
     *
     * @throws ValidationException
     */
    public function execute(array $data, array $reportIds): void
    {
        $this->validateScheduledAtAgainstReports($data, $reportIds);
        $this->validateStartedAtAgainstScheduledAt($data);
        $this->validateCompletedAtAgainstStartedAt($data);
    }

    /**
     * Ensure scheduled_at is not before the latest report creation date.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string>  $reportIds
     *
     * @throws ValidationException
     */
    private function validateScheduledAtAgainstReports(array $data, array $reportIds): void
    {
        if (empty($reportIds) || ! filled($data['scheduled_at'] ?? null)) {
            return;
        }

        $latestReportCreatedAt = Report::query()
            ->whereIn('id', $reportIds)
            ->max('created_at');

        if ($latestReportCreatedAt === null) {
            return;
        }

        if (Carbon::parse($data['scheduled_at'])->lt(Carbon::parse($latestReportCreatedAt))) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('validation.after_or_equal', [
                    'attribute' => __('filament.admin.fields_common.scheduled_at'),
                    'date' => $latestReportCreatedAt,
                ]),
            ]);
        }
    }

    /**
     * Ensure started_at is not before scheduled_at.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validateStartedAtAgainstScheduledAt(array $data): void
    {
        if (! filled($data['scheduled_at'] ?? null) || ! filled($data['started_at'] ?? null)) {
            return;
        }

        if (Carbon::parse($data['started_at'])->lt(Carbon::parse($data['scheduled_at']))) {
            throw ValidationException::withMessages([
                'started_at' => __('validation.after_or_equal', [
                    'attribute' => __('filament.admin.fields_common.started_at'),
                    'date' => $data['scheduled_at'],
                ]),
            ]);
        }
    }

    /**
     * Ensure completed_at is not before started_at.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validateCompletedAtAgainstStartedAt(array $data): void
    {
        if (! filled($data['started_at'] ?? null) || ! filled($data['completed_at'] ?? null)) {
            return;
        }

        if (Carbon::parse($data['completed_at'])->lt(Carbon::parse($data['started_at']))) {
            throw ValidationException::withMessages([
                'completed_at' => __('validation.after_or_equal', [
                    'attribute' => __('filament.admin.fields_common.completed_at'),
                    'date' => $data['started_at'],
                ]),
            ]);
        }
    }
}

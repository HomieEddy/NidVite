<?php

namespace App\Listeners;

use App\Enums\ReportStatus;
use App\Events\ReportCreated;
use Illuminate\Support\Facades\Log;

class AutoTriageCreatedReport
{
    public function handle(ReportCreated $event): void
    {
        $report = $event->report->fresh();

        if ($report === null || $report->status !== ReportStatus::Received->value) {
            return;
        }

        try {
            if ((bool) $report->is_spam) {
                $report->transitionTo(ReportStatus::Rejected->value, __('report.auto_rejection.spam'));

                return;
            }

            if ($this->shouldRejectByRoadValidation($report->road_validation_decision, $report->road_validation_mode)) {
                $report->transitionTo(ReportStatus::Rejected->value, __('report.auto_rejection.road_validation'));

                return;
            }

            $report->transitionTo(ReportStatus::Verified->value);
        } catch (\Throwable $exception) {
            Log::warning('Automatic report triage failed; report remains in current status.', [
                'report_id' => $report->getKey(),
                'status' => $report->status,
                'exception_class' => $exception::class,
            ]);
        }
    }

    private function shouldRejectByRoadValidation(?string $decision, ?string $mode): bool
    {
        if ($mode !== 'enforce') {
            return false;
        }

        return in_array($decision, ['fail_off_street', 'fail_both'], true);
    }
}

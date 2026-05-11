<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Jobs\SendCriticalAlertEmailJob;
use App\Models\EmailDeliveryLog;
use App\Models\User;

class SendCriticalReportAlerts
{
    public function handle(ReportCreated $event): void
    {
        $report = $event->report;

        if (! in_array($report->priority, ['high', 'critical'], true)) {
            return;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('slug', ['admin', 'manager']))
            ->get();

        foreach ($recipients as $recipient) {
            $log = EmailDeliveryLog::query()->firstOrCreate(
                [
                    'report_id' => $report->id,
                    'user_id' => $recipient->id,
                    'kind' => 'critical_alert',
                ],
                [
                    'status' => 'pending',
                    'attempts' => 0,
                ]
            );

            if (! $log->wasRecentlyCreated) {
                continue;
            }

            SendCriticalAlertEmailJob::dispatch($report->id, $recipient->id, $log->id)->afterCommit();
        }
    }
}

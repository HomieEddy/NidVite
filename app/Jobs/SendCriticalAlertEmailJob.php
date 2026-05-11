<?php

namespace App\Jobs;

use App\Models\EmailDeliveryLog;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CriticalReportAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCriticalAlertEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $afterCommit = true;

    public int $tries = 3;

    public function __construct(
        public int $reportId,
        public int $userId,
        public int $deliveryLogId
    ) {}

    public function handle(): void
    {
        $report = Report::query()->find($this->reportId);
        $user = User::query()->find($this->userId);
        $log = EmailDeliveryLog::query()->find($this->deliveryLogId);
        if (! $log) {
            return;
        }

        if (! $report || ! $user) {
            $log->last_error = 'Critical alert delivery aborted: missing report or user.';
            $log->transitionTo('permanent_failed');
            $log->save();

            Log::warning('Critical alert delivery aborted due to missing resources.', [
                'report_id' => $this->reportId,
                'user_id' => $this->userId,
                'delivery_log_id' => $this->deliveryLogId,
            ]);

            return;
        }

        $log->attempts = $log->attempts + 1;
        $log->transitionTo('sending');
        $log->save();

        $user->notify(new CriticalReportAlertNotification($report));

        $log->last_error = null;
        $log->transitionTo('delivered');
        $log->save();
    }

    public function failed(Throwable $exception): void
    {
        $log = EmailDeliveryLog::query()->find($this->deliveryLogId);
        if (! $log) {
            return;
        }

        $log->last_error = $exception->getMessage();
        $log->transitionTo('permanent_failed');
        $log->save();
    }
}

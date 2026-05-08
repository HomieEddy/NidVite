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
use Throwable;

class SendCriticalAlertEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $reportId,
        public int $userId,
        public int $deliveryLogId
    ) {}

    public function handle(): void
    {
        $report = Report::query()->findOrFail($this->reportId);
        $user = User::query()->findOrFail($this->userId);

        $log = EmailDeliveryLog::query()->findOrFail($this->deliveryLogId);
        $log->attempts = $log->attempts + 1;
        $log->status = 'sending';
        $log->save();

        $user->notify(new CriticalReportAlertNotification($report));

        $log->status = 'delivered';
        $log->delivered_at = now();
        $log->last_error = null;
        $log->save();
    }

    public function failed(Throwable $exception): void
    {
        $log = EmailDeliveryLog::query()->find($this->deliveryLogId);
        if (! $log) {
            return;
        }

        $log->status = 'permanent_failed';
        $log->failed_at = now();
        $log->last_error = $exception->getMessage();
        $log->save();
    }
}

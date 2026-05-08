<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalReportAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'critical_report_alert',
            'report_id' => $this->report->id,
            'tracking_id' => $this->report->public_tracking_id,
            'status' => $this->report->status,
            'priority' => $this->report->priority,
            'address' => $this->report->address,
            'message_key' => 'filament.notifications.critical_report.message',
            'url' => route('report.tracking', $this->report->public_tracking_id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('filament.notifications.critical_report.subject'))
            ->line(__('filament.notifications.critical_report.message'))
            ->line(__('filament.notifications.critical_report.address').': '.($this->report->address ?? '-'))
            ->line(__('filament.notifications.critical_report.priority').': '.$this->report->priority)
            ->action(__('filament.notifications.critical_report.action'), route('report.tracking', $this->report->public_tracking_id));
    }
}

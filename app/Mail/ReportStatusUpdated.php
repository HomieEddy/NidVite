<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Report $report,
        public string $oldStatus,
        public ?string $unsubscribeUrl = null,
        public ?string $localeOverride = null,
    ) {}

    public function envelope(): Envelope
    {
        $locale = $this->localeOverride ?? $this->report->preferred_locale ?? 'fr';

        return new Envelope(
            subject: __('email.status_updated.subject', ['status' => __("status.{$this->report->status}")], $locale),
        );
    }

    public function content(): Content
    {
        $locale = $this->localeOverride ?? $this->report->preferred_locale ?? 'fr';

        return new Content(
            markdown: 'emails.report-status-updated',
            with: [
                'report' => $this->report,
                'oldStatus' => $this->oldStatus,
                'locale' => $locale,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}

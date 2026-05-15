<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyOperationsDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public array $summary,
        public string $digestLocale = 'fr',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.weekly_digest.subject', [
                'start' => (string) ($this->summary['window']['start'] ?? ''),
                'end' => (string) ($this->summary['window']['end'] ?? ''),
            ], $this->digestLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-operations-digest',
            with: [
                'summary' => $this->summary,
                'locale' => $this->digestLocale,
            ],
        );
    }
}

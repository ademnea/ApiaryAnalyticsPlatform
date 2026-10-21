<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledMonitoringReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportName,
        public array $reportData,
        public bool $attachCsv,
        public ?string $csv = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "AdEMNEA report: {$this->reportName}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.scheduled-monitoring-report');
    }

    public function attachments(): array
    {
        if (! $this->attachCsv || $this->csv === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->csv, 'sensor-anomaly-report.csv')
                ->withMime('text/csv'),
        ];
    }
}

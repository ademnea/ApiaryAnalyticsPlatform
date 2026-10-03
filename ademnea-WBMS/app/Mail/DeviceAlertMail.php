<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** An IoT condition monitoring alert, recovery or malformed-heartbeat notice for admins and hardware teams. */
class DeviceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $alertSubject, public string $body)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.device-alert');
    }
}

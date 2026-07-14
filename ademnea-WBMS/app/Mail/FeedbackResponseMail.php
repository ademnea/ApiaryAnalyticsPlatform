<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Feedback $feedback)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Response to your feedback: ' . $this->feedback->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback-response',
        );
    }
}

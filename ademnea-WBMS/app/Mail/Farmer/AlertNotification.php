<?php

namespace App\Mail\Farmer;

use App\Models\Farmer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Farmer $farmer;
    public string $subject;
    public string $content;

    public function __construct(Farmer $farmer, string $subject, string $content)
    {
        $this->farmer = $farmer;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA Alert - ' . $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.alert-notification',
            with: [
                'name' => $this->farmer->user->name,
                'subject' => $this->subject,
                'content' => $this->content,
                'appUrl' => config('app.mobile_app_url'),
            ],
        );
    }
}
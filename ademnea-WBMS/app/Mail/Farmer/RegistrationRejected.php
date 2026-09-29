<?php

namespace App\Mail\Farmer;

use App\Models\Farmer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * UC-FAPI-01 (admin rejection). The message deliberately gives a contact
 * route rather than a reason: rejection usually means "not a recognised
 * project participant", which is resolved by talking to the team.
 */
class RegistrationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Farmer $farmer, public ?string $reason = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA - About your registration',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.registration-rejected',
            with: [
                'name'         => $this->farmer->full_name,
                'reason'       => $this->reason,
                'contactEmail' => config('mail.from.address'),
            ],
        );
    }
}

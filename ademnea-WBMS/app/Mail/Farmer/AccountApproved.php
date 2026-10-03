<?php

namespace App\Mail\Farmer;

use App\Models\Farmer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * UC-FAPI-01 (admin approval): the farmer may now sign in to the mobile app.
 */
class AccountApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Farmer $farmer)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA - Your account has been activated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.account-approved',
            with: [
                'name'     => $this->farmer->full_name,
                'loginUrl' => config('app.mobile_app_url'),
            ],
        );
    }
}

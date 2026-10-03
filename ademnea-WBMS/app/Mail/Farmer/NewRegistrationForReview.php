<?php

namespace App\Mail\Farmer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * UC-FAPI-01: tells approvers that a farmer is waiting in the pending queue.
 */
class NewRegistrationForReview extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA - A farmer registration is awaiting approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.new-registration-admin',
            with: [
                'name'       => $this->user->name,
                'email'      => $this->user->email,
                'pendingUrl' => route('admin.farmers.pending'),
            ],
        );
    }
}

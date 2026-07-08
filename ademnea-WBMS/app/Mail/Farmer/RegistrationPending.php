<?php

namespace App\Mail\Farmer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationPending extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA - Registration Pending Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.registration-pending',
            with: [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        );
    }
}
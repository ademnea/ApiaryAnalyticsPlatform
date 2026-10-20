<?php

namespace App\Mail\Farmer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FarmerPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $code;

    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AdEMNEA - Password Reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.farmer.password-reset',
            with: [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'code' => $this->code,
            ],
        );
    }
}

<?php

namespace App\Mail\Farmer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $token;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
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
                'token' => $this->token,
                'resetUrl' => config('app.mobile_app_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email),
            ],
        );
    }
}
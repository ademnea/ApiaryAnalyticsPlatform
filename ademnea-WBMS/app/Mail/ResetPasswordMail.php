<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ResetPasswordMail
 *
 * Sent by User::sendPasswordResetNotification() via the Password broker.
 * The plain-text token is embedded in the reset URL; the DB stores a bcrypt hash.
 * Laravel's Password broker handles hash comparison on submission.
 *
 * REQ-F-AUTH-04 — password-reset email with token link expiring in 60 minutes.
 */
class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $token  The plain-text reset token (DB stores bcrypt hash)
     * @param  string  $email  The user's email address
     */
    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your AdEMNEA Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $resetUrl = url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $this->email,
        ], false));

        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'resetUrl'         => $resetUrl,
                'email'            => $this->email,
                'expiresInMinutes' => 60,
            ],
        );
    }
}

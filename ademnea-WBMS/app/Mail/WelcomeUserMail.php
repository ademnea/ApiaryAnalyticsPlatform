<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * WelcomeUserMail
 *
 * Sent immediately after an admin creates a new user account.
 * Carries the plain-text password ONE TIME ONLY — not stored after dispatch.
 *
 * REQ-F-UADM-01 criterion 4 — welcome email with account email and plain-text password.
 * REQ 17.4 — plain-text password only transmitted in this one-time welcome email.
 */
class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $user           The newly created user
     * @param  string            $plainPassword  Plain-text password — one-time only, NOT stored
     */
    public function __construct(
        public readonly User   $user,
        public readonly string $plainPassword,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to AdEMNEA Analytics Platform',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.welcome-user',
            with: [
                'user'          => $this->user,
                'plainPassword' => $this->plainPassword,
                'loginUrl'      => url(route('admin.login', [], false)),
            ],
        );
    }
}

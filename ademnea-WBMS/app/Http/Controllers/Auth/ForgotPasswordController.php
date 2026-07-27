<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;

/**
 * ForgotPasswordController
 *
 * Handles the "forgot password" flow.
 * Non-enumeration: always returns the same response regardless of whether
 * the email is registered (REQ 18.2, REQ 4.2, REQ 4.4).
 */
class ForgotPasswordController extends Controller
{
    /** Show the forgot-password request form. */
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password-reset link to the given email.
     *
     * Validates email FORMAT only (no exists:users rule — prevents enumeration).
     * Returns an identical success response for both registered and unregistered emails.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt to send the reset link — broker only sends if user exists
        Password::broker()->sendResetLink(
            $request->only('email')
        );

        // Always return the same success response (REQ 18.2 — non-enumeration)
        return back()->with('status', __('passwords.sent'));
    }
}

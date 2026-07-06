<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * LoginController
 *
 * Handles admin / staff dashboard authentication.
 * Implements:
 *   REQ-F-AUTH-01 — Login endpoint at /admin/login
 *   REQ-F-AUTH-02 — Secure session logout
 *   REQ-F-AUTH-03 — Brute-force rate limiting (10 attempts / 1 min per IP)
 */
class LoginController extends Controller
{
    /** Show the login form. */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /** Handle a login request. */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // --- Rate limiting (REQ-F-AUTH-03) ---------------------------------
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds,
                ]),
            ]);
        }

        // --- Attempt auth --------------------------------------------------
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60); // 1-minute window

            // Generic message — does NOT reveal whether email or password was wrong
            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        // --- Check account status ------------------------------------------
        $user = Auth::user();

        if ($user->status === 'pending') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account is awaiting administrator approval.'),
            ]);
        }

        if ($user->status === 'suspended') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account has been suspended. Please contact the administrator.'),
            ]);
        }

        if ($user->status === 'rejected') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account registration was not approved.'),
            ]);
        }

        // Prevent farmers from accessing admin dashboard (BR-10)
        if ($user->role === 'farmer') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Farmer accounts cannot access the admin dashboard.'),
            ]);
        }

        // --- Success -------------------------------------------------------
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->first_name . '!');
    }

    /** Log the user out and destroy the session. */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'You have been signed out successfully.');
    }
}
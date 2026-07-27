<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * LoginController
 *
 * Handles admin / staff dashboard authentication.
 * REQ-F-AUTH-01 — Login endpoint at /admin/login
 * REQ-F-AUTH-02 — Secure session logout
 * REQ-F-AUTH-03 — Brute-force rate limiting (5 attempts / 900s per email+IP)
 */
class LoginController extends Controller
{
    /** Show the login form. */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /** Handle a login request. */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Rate limiting: 5 attempts / 900-second window (REQ-F-AUTH-03)
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds,
                ]),
            ]);
        }

        // Attempt authentication
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 900); // 15-minute window

            // Generic message — does NOT reveal whether email or password was wrong (REQ 18.1)
            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        // Check account status (REQ 1.7–1.9)
        $user = Auth::user();

        if ($user->status === 'pending') {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'email' => __('Your account is awaiting administrator approval.'),
            ]);
        }

        if ($user->status === 'suspended') {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'email' => __('Your account has been suspended. Please contact the administrator.'),
            ]);
        }

        if ($user->status === 'rejected') {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'email' => __('Your account registration was not approved.'),
            ]);
        }

        // Block farmer-only accounts from admin dashboard (REQ 1.10).
        // Users with 'farmer' AND another role (e.g. researcher, admin) are allowed through.
        $roles        = $user->getRoleNames();
        $isFarmerOnly = $roles->count() > 0 && $roles->every(fn ($r) => $r === 'farmer');

        if ($isFarmerOnly) {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages([
                'email' => __('Farmer accounts cannot access the admin dashboard.'),
            ]);
        }

        // Success
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /** Log the user out and destroy the session (REQ-F-AUTH-02). */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('status', 'You have been signed out successfully.');
    }
}

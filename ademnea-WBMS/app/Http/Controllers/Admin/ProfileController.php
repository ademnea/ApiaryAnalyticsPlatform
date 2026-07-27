<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * ProfileController
 *
 * Handles authenticated user's self-service profile management.
 * All routes protected by 'auth' middleware at the route-group level (REQ 5.8).
 *
 * REQ-F-AUTH-06 — name/email update
 * REQ-F-AUTH-07 — password change
 */
class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     * Pre-populated with the authenticated user's current name and email (REQ 5.1, 5.2).
     */
    public function show(): View
    {
        return view('admin.profile', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the authenticated user's name and/or email (REQ 5.3, 5.4, 5.5).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return redirect()->route('admin.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Change the authenticated user's password (REQ 5.6, 5.7).
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        // Verify current password before allowing change (REQ 5.7)
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided current password is incorrect.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.profile')
            ->with('success', 'Password changed successfully.');
    }
}

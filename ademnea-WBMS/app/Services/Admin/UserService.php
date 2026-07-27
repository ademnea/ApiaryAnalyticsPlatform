<?php

namespace App\Services\Admin;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * UserService
 *
 * Encapsulates all business logic for admin user management.
 * Keeps UserController thin — it only handles HTTP concerns
 * (validation, redirects, responses).
 *
 * REQ-F-UADM-01 to 04 — User lifecycle management
 * REQ-F-RBAC-03       — Role assignment
 */
class UserService
{
    /**
     * Create a new user, assign roles, and dispatch the welcome email.
     *
     * Returns an array with:
     *   - 'user'         => the created User model
     *   - 'emailFailed'  => bool — true if the welcome email could not be sent
     *
     * REQ-F-UADM-01
     */
    public function createUser(array $data): array
    {
        // Permanently remove any soft-deleted record with this email so the
        // DB unique constraint doesn't fire (SQLite doesn't support partial indexes).
        User::withTrashed()
            ->where('email', $data['email'])
            ->whereNotNull('deleted_at')
            ->forceDelete();

        $plainPassword = $data['password'];

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($plainPassword),
            'status'    => 'active',
            'is_active' => true,
        ]);

        $user->syncRoles($data['roles']);

        $emailFailed = false;

        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $plainPassword));
        } catch (\Throwable $e) {
            Log::warning("WelcomeUserMail failed for user [{$user->id}]: " . $e->getMessage());
            $emailFailed = true;
        }

        return compact('user', 'emailFailed');
    }

    /**
     * Update a user's profile, password (optional), and roles.
     *
     * REQ-F-UADM-02
     */
    public function updateUser(User $user, array $data, ?string $newPassword): void
    {
        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if ($newPassword) {
            $payload['password'] = Hash::make($newPassword);
        }

        $user->update($payload);
        $user->syncRoles($data['roles']);
    }

    /**
     * Validate self-edit constraints — a user cannot change their own roles
     * or account status via the admin UI.
     *
     * Returns an error message string, or null if the edit is permitted.
     *
     * REQ-F-UADM-02 (self-edit guards)
     */
    public function validateSelfEdit(User $user, array $incomingRoles, int $actorId): ?string
    {
        if ($user->id !== $actorId) {
            return null; // Not editing self — no restriction
        }

        $currentRoles = $user->getRoleNames()->toArray();
        sort($currentRoles);
        sort($incomingRoles);

        if ($incomingRoles !== $currentRoles) {
            return 'You cannot modify your own roles via the admin UI.';
        }

        return null;
    }

    /**
     * Check whether a non-super-admin is attempting to assign the super-admin role.
     *
     * REQ-F-RBAC-03 (super-admin role protection)
     */
    public function canAssignSuperAdmin(array $roles, User $actor): bool
    {
        if (! in_array('super-admin', $roles)) {
            return true; // Not assigning super-admin — always allowed
        }

        return $actor->hasRole('super-admin');
    }

    /**
     * Activate a user account.
     *
     * REQ-F-UADM-03
     */
    public function activateUser(User $user): void
    {
        $user->update(['status' => 'active', 'is_active' => true]);
    }

    /**
     * Suspend a user account with guard checks.
     *
     * Returns an error message string, or null on success.
     *
     * REQ-F-UADM-03
     */
    public function suspendUser(User $user, int $actorId): ?string
    {
        if ($user->id === $actorId) {
            return 'You cannot suspend your own account.';
        }

        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return 'The last super-admin account cannot be suspended.';
        }

        $user->update(['status' => 'suspended', 'is_active' => false]);

        return null;
    }

    /**
     * Soft-delete a user account with guard checks.
     *
     * Returns an error message string, or null on success.
     *
     * REQ-F-UADM-03
     */
    public function deleteUser(User $user, int $actorId): ?string
    {
        if ($user->id === $actorId) {
            return 'You cannot delete your own account.';
        }

        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return 'The last super-admin account cannot be deleted.';
        }

        $user->delete();

        return null;
    }
}

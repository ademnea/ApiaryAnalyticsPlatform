<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\User;
use App\Models\FarmerAuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new farmer (pending approval)
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create user with farmer role and pending status
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'farmer',
                'status' => 'pending',
            ]);

            // Create farmer record
            $farmer = Farmer::create([
                'user_id' => $user->id,
                'telephone' => $data['telephone'] ?? null,
            ]);

            // Send confirmation email to farmer
            Mail::to($user->email)->send(new \App\Mail\Farmer\RegistrationPending($user));

            // TODO: Send notification to admin

            return [
                'user' => $user,
                'farmer' => $farmer,
            ];
        });
    }

    /**
     * Login farmer and issue token
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Check account status
        if ($user->status === 'pending') {
            return ['error' => 'pending', 'message' => 'Your account is awaiting administrator approval.'];
        }

        if ($user->status === 'rejected') {
            return ['error' => 'rejected', 'message' => 'Your account registration was not approved. Please contact the project team.'];
        }

        if ($user->status !== 'active') {
            return ['error' => 'inactive', 'message' => 'Your account is inactive. Please contact the administrator.'];
        }

        // Check role
        if ($user->role !== 'farmer') {
            return ['error' => 'invalid_role', 'message' => 'This account does not have farmer access.'];
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token with 30-day expiry
        $token = $user->createToken('farmer-token', ['*'], Carbon::now()->addDays(30));

        // Get farmer profile
        $farmer = Farmer::where('user_id', $user->id)->first();

        return [
            'token' => $token->plainTextToken,
            'expires_at' => Carbon::now()->addDays(30)->toIso8601String(),
            'farmer' => [
                'id' => $farmer->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
    }

    /**
     * Logout farmer - revoke current token
     */
    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Send password reset email
     */
    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user || $user->role !== 'farmer') {
            return; // Silent return to prevent enumeration
        }

        $token = Str::random(60);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        Mail::to($email)->send(new \App\Mail\Farmer\PasswordReset($user, $token));
    }

    /**
     * Reset password using token
     */
    public function resetPassword(array $data): bool
    {
        $reset = DB::table('password_resets')
            ->where('email', $data['email'])
            ->first();

        if (!$reset || !Hash::check($data['token'], $reset->token)) {
            return false;
        }

        // Check if token is expired (60 minutes)
        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return false;
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user || $user->role !== 'farmer') {
            return false;
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // Delete the reset token
        DB::table('password_resets')->where('email', $data['email'])->delete();

        return true;
    }

    /**
     * Update farmer profile
     */
    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $farmer = Farmer::where('user_id', $user->id)->first();

            // Update user
            $userData = [];
            if (isset($data['password']) && !empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            if (isset($data['name'])) {
                $userData['name'] = $data['name'];
            }
            if (!empty($userData)) {
                $user->update($userData);
            }

            // Update farmer
            $farmerData = [];
            if (isset($data['address'])) {
                $farmerData['address'] = $data['address'];
            }
            if (isset($data['telephone'])) {
                $farmerData['telephone'] = $data['telephone'];
            }
            if (!empty($farmerData)) {
                $farmer->update($farmerData);
            }

            // Log the update
            FarmerAuditLog::create([
                'farmer_id' => $farmer->id,
                'action_type' => 'profile_update',
                'affected_record_type' => 'farmer',
                'affected_record_id' => $farmer->id,
                'details' => json_encode(array_keys($farmerData)),
            ]);

            return [
                'farmer' => $farmer->fresh(),
                'user' => $user->fresh(),
            ];
        });
    }

    /**
     * Get farmer profile
     */
    public function getProfile(User $user): array
    {
        $farmer = Farmer::with('user')->where('user_id', $user->id)->first();

        return [
            'id' => $farmer->id,
            'first_name' => $user->name,
            'last_name' => '', // Can be extended if needed
            'email' => $user->email,
            'telephone' => $farmer->telephone,
            'address' => $farmer->address,
            'gender' => $farmer->gender,
            'role' => $user->role,
        ];
    }

    /**
     * Register FCM device token
     */
    public function registerDeviceToken(User $user, string $deviceToken): void
    {
        $farmer = Farmer::where('user_id', $user->id)->first();

        // Update token (upsert pattern)
        $farmer->update(['fcm_token' => $deviceToken]);

        // Log the action
        FarmerAuditLog::create([
            'farmer_id' => $farmer->id,
            'action_type' => 'device_token_registered',
            'affected_record_type' => 'farmer',
            'affected_record_id' => $farmer->id,
        ]);
    }
}
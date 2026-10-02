<?php

namespace App\Services\Farmer;

use App\Mail\Farmer\PasswordReset;
use App\Mail\Farmer\RegistrationPending;
use App\Models\Farmer;
use App\Models\FarmerAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Farmer mobile API authentication.
 *
 * A farmer logs in as a User (users.role = 'farmer', Spatie role 'farmer')
 * linked to a Farmer profile (farmers.user_id). Self-registered accounts start
 * as users.status = 'pending' / farmers.profile_status = 'pending' and appear
 * in the admin "Pending approvals" list; approving them activates both.
 */
class AuthService
{
    private const RESET_TOKEN_TTL_MINUTES = 60;

    public function register(array $data): array
    {
        [$user, $farmer] = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'farmer',
                'status' => 'pending',
            ]);

            // The Spatie role is what keeps farmers out of the admin dashboard
            // (EnsureNotFarmer) and lets them through role:farmer on the API.
            $user->assignRole(Role::findOrCreate('farmer', 'web'));

            [$firstName, $lastName] = array_pad(explode(' ', trim($data['name']), 2), 2, null);

            $farmer = Farmer::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'telephone' => $data['telephone'] ?? null,
                'profile_status' => 'pending',
                'registration_date' => now(),
            ]);

            return [$user, $farmer];
        });

        // Queued so a slow or failing mail server never breaks registration.
        Mail::to($user->email)->queue(new RegistrationPending($user));

        return ['user' => $user, 'farmer' => $farmer];
    }

    /**
     * @return array{token: string, expires_at: string, farmer: array}|array{error: string, message: string}|null
     *         null = wrong email/password
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        if ($user->role !== 'farmer') {
            return ['error' => 'invalid_role', 'message' => 'This account does not have farmer access.'];
        }

        if ($user->status === 'pending') {
            return ['error' => 'pending', 'message' => 'Your account is awaiting administrator approval.'];
        }

        if ($user->status === 'rejected') {
            return ['error' => 'rejected', 'message' => 'Your account registration was not approved. Please contact the project team.'];
        }

        if ($user->status !== 'active') {
            return ['error' => 'inactive', 'message' => 'Your account is inactive. Please contact the administrator.'];
        }

        $farmer = Farmer::where('user_id', $user->id)->first();
        if (! $farmer) {
            return ['error' => 'no_profile', 'message' => 'No farmer profile is linked to this account. Please contact the administrator.'];
        }

        // One active session per farmer: revoke older tokens.
        $user->tokens()->delete();

        $expiresAt = Carbon::now()->addDays(30);
        $token = $user->createToken('farmer-token', ['*'], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'farmer' => [
                'id' => $farmer->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user || $user->role !== 'farmer') {
            return; // Silent, so the endpoint can't be used to discover accounts.
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        Mail::to($email)->queue(new PasswordReset($user, $token));
    }

    public function resetPassword(array $data): bool
    {
        $reset = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $reset || ! Hash::check($data['token'], $reset->token)) {
            return false;
        }

        if (Carbon::parse($reset->created_at)->addMinutes(self::RESET_TOKEN_TTL_MINUTES)->isPast()) {
            return false;
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user || $user->role !== 'farmer') {
            return false;
        }

        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete(); // Sign out every device after a password reset.

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return true;
    }

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $farmer = Farmer::where('user_id', $user->id)->firstOrFail();

            $userData = [];
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            if (isset($data['name'])) {
                $userData['name'] = $data['name'];
            }
            if ($userData) {
                $user->update($userData);
            }

            $farmerData = array_intersect_key($data, array_flip(['address', 'telephone']));
            if ($farmerData) {
                $farmer->update($farmerData);
            }

            FarmerAuditLog::create([
                'farmer_id' => $farmer->id,
                'action_type' => 'profile_update',
                'affected_record_type' => 'farmer',
                'affected_record_id' => $farmer->id,
                'details' => json_encode(array_keys($farmerData + $userData)),
            ]);

            return ['farmer' => $farmer->fresh(), 'user' => $user->fresh()];
        });
    }

    public function getProfile(User $user): array
    {
        $farmer = Farmer::where('user_id', $user->id)->firstOrFail();

        return [
            'id' => $farmer->id,
            'name' => $user->name,
            'first_name' => $farmer->first_name,
            'last_name' => $farmer->last_name,
            'email' => $user->email,
            'telephone' => $farmer->telephone,
            'address' => $farmer->address,
            'gender' => $farmer->gender,
            'role' => $user->role,
        ];
    }

    public function registerDeviceToken(User $user, string $deviceToken): void
    {
        $farmer = Farmer::where('user_id', $user->id)->firstOrFail();

        $farmer->update(['fcm_token' => $deviceToken]);

        FarmerAuditLog::create([
            'farmer_id' => $farmer->id,
            'action_type' => 'device_token_registered',
            'affected_record_type' => 'farmer',
            'affected_record_id' => $farmer->id,
        ]);
    }
}

<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Services\Farmer\FarmerAuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * UC-FAPI-01 to 05: Farmer authentication and account management.
 * All business logic lives here — controllers delegate, never decide.
 */
class FarmerAuthService
{
    public function __construct(
        private readonly FarmerAuditService $audit
    ) {}

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-01: Farmer self-registration
    // -------------------------------------------------------------------------
    public function register(array $data): Farmer
    {
        return DB::transaction(function () use ($data) {
            $farmer = Farmer::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'telephone' => $data['telephone'],
                'password'  => Hash::make($data['password']),
                'status'    => 'pending', // Admin must approve before login succeeds
            ]);

            // Assign farmer role (Spatie laravel-permission — Dev A's setup)
            $farmer->assignRole('farmer');

            return $farmer;
        });
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-02: Login — issues 30-day Sanctum token
    // -------------------------------------------------------------------------
    public function login(array $credentials): array
    {
        $farmer = Farmer::where('email', $credentials['email'])->first();

        if (!$farmer || !Hash::check($credentials['password'], $farmer->password)) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if ($farmer->status !== 'active') {
            return ['success' => false, 'message' => 'Your account is pending admin approval.'];
        }

        // Revoke any existing tokens to enforce single-session (one device at a time)
        $farmer->tokens()->delete();

        $token = $farmer->createToken('farmer-mobile', ['*'], now()->addDays(30))->plainTextToken;

        return [
            'success' => true,
            'token'   => $token,
            'farmer'  => $farmer,
        ];
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-03: Logout — revoke current token
    // -------------------------------------------------------------------------
    public function logout(Farmer $farmer): void
    {
        $farmer->currentAccessToken()->delete();
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-04: Password reset (2-step: forgot → reset)
    // -------------------------------------------------------------------------
    public function sendPasswordResetLink(string $email): bool
    {
        // Always returns true to avoid email enumeration
        Password::broker('farmers')->sendResetLink(['email' => $email]);
        return true;
    }

    public function resetPassword(array $data): bool
    {
        $status = Password::broker('farmers')->reset(
            [
                'email'                 => $data['email'],
                'password'              => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token'                 => $data['token'],
            ],
            function (Farmer $farmer, string $password) {
                $farmer->forceFill(['password' => Hash::make($password)])
                       ->setRememberToken(Str::random(60));
                $farmer->save();
                event(new PasswordReset($farmer));
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-05: Profile update
    // -------------------------------------------------------------------------
    public function updateProfile(Farmer $farmer, array $data): Farmer
    {
        // Verify current password before allowing password change
        if (isset($data['password'])) {
            if (!Hash::check($data['current_password'], $farmer->password)) {
                throw new \InvalidArgumentException('Current password is incorrect.');
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password'], $data['password_confirmation']);
        }

        $allowed = array_intersect_key($data, array_flip(['name', 'email', 'telephone', 'password']));
        $farmer->update($allowed);

        $this->audit->log($farmer->id, 'profile_update', $farmer->id);

        return $farmer->fresh();
    }
}

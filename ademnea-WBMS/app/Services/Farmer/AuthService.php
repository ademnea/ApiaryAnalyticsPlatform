<?php

namespace App\Services\Farmer;

use App\Mail\Farmer\FarmerPasswordReset;
use App\Mail\Farmer\NewRegistrationForReview;
use App\Mail\Farmer\RegistrationPending;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * UC-FAPI-01 to 05: farmer authentication and account management.
 *
 * Identity lives on `User` — it is the only model with a password, Sanctum
 * tokens and Spatie roles. `Farmer` is the profile row hanging off it, and
 * the account lifecycle (pending / active / rejected / suspended) is carried
 * by `users.status`. `farmers.status` is the operational flag and cannot hold
 * a pending state: it is enum('Active','Inactive','Suspended') with a CHECK
 * constraint behind it.
 */
class AuthService
{
    /**
     * Longer than an enterprise default on purpose: farmers are in rural areas
     * with intermittent connectivity, and forcing a re-login on a dropped
     * connection is a worse failure than a longer-lived token.
     */
    private const TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly FarmerProfileLinker $linker,
        private readonly FarmerAuditService $audit,
    ) {}

    // -------------------------------------------------------------------------
    // UC-FAPI-01 — self-registration (account starts pending)
    // -------------------------------------------------------------------------

    /**
     * @return array{user: User, farmer: Farmer}
     */
    public function register(array $data): array
    {
        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                // The `hashed` cast on User hashes this; do not pre-hash.
                'password' => $data['password'],
                'role'     => 'farmer',
                'status'   => 'pending',
            ]);

            // No Spatie role is assigned here. The SRS gives that decision to
            // the administrator at approval time, who also chooses between
            // farmer and farmer-write.

            $farmer = $this->linker->linkOrCreate($user, [
                'telephone'  => $data['telephone'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name'  => $data['last_name'] ?? null,
            ]);

            $this->audit->log($farmer->id, 'registration_submitted', $farmer->id);

            return ['user' => $user, 'farmer' => $farmer];
        });

        // Outside the transaction: a mail failure must not roll back a
        // perfectly good registration.
        $this->sendQuietly(
            fn () => Mail::to($result['user']->email)->send(new RegistrationPending($result['user'])),
            'farmer registration confirmation'
        );

        $this->sendQuietly(function () use ($result) {
            $approvers = User::permission('approve-farmer-registrations')->pluck('email')->all();

            if ($approvers !== []) {
                Mail::to($approvers)->send(new NewRegistrationForReview($result['user']));
            }
        }, 'admin new-registration notice');

        return $result;
    }

    // -------------------------------------------------------------------------
    // UC-FAPI-02 — login
    // -------------------------------------------------------------------------

    /**
     * @return array{token: string, expires_at: string, farmer: array}|array{error: string, message: string}|null
     *         null means invalid credentials.
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Checked before the status branches and folded into the generic
        // failure: telling a caller "this account is not a farmer" would let
        // them enumerate admin addresses.
        if ($user->role !== 'farmer') {
            return null;
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

        $farmer = $this->resolveFarmer($user);

        // Only now, after every rejection path: a failed login must never
        // revoke a session the farmer still has on another device.
        $user->tokens()->delete();

        // One timestamp for both the token and the response, so the client is
        // never told an expiry that differs from the one being enforced.
        $expiresAt = Carbon::now()->addDays(self::TOKEN_TTL_DAYS);

        $token = $user->createToken('farmer-mobile', ['*'], $expiresAt);

        $farmer->forceFill(['last_login_at' => now()])->save();

        return [
            'token'      => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'farmer'     => [
                'id'    => $farmer->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // UC-FAPI-03 — logout
    // -------------------------------------------------------------------------

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    // -------------------------------------------------------------------------
    // UC-FAPI-04 — password recovery
    // -------------------------------------------------------------------------

    /**
     * Always returns void: the controller answers identically whether or not
     * the address is registered.
     *
     * Uses the framework's password broker rather than hand-rolled token rows.
     * config/auth.php already configures password_reset_tokens with a 60
     * minute expiry and a throttle, the repository stores the token hashed,
     * and it deletes the row on use — which is the single-use requirement.
     */
    public function sendResetLink(string $email): void
    {
        $this->sendQuietly(function () use ($email) {
            Password::broker('users')->sendResetLink(
                // `role` is not a password key, so the user provider turns it
                // into a where clause: an administrator can never be issued a
                // mobile deep link.
                ['email' => $email, 'role' => 'farmer'],
                fn (User $user, string $token) => Mail::to($user->email)
                    ->send(new FarmerPasswordReset($user, $token))
            );
        }, 'farmer password reset link');
    }

    public function resetPassword(array $data): bool
    {
        $status = Password::broker('users')->reset(
            [
                'email'                 => $data['email'],
                'password'              => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? $data['password'],
                'token'                 => $data['token'],
                'role'                  => 'farmer',
            ],
            function (User $user, string $password) {
                // The `hashed` cast hashes this on save.
                $user->forceFill(['password' => $password])->save();

                // Deliberately NOT revoking Sanctum tokens. UC-FAPI-04 states
                // that existing sessions survive a password reset; do not
                // "fix" this into a global logout.
            }
        );

        // Every failure — unknown address, wrong token, expired token —
        // collapses to false so the caller emits one message and leaks nothing.
        return $status === Password::PASSWORD_RESET;
    }

    // -------------------------------------------------------------------------
    // UC-FAPI-05 — profile
    // -------------------------------------------------------------------------

    public function getProfile(User $user): array
    {
        $farmer = $this->resolveFarmer($user);

        [$firstName, $lastName] = $this->linker->splitName(
            $user->name,
            $farmer->first_name,
            $farmer->last_name
        );

        return [
            'id'         => $farmer->id,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'gender'     => $farmer->gender,
            'email'      => $user->email,
            'telephone'  => $farmer->telephone,
            'address'    => $farmer->address,
            'role'       => $user->role,
        ];
    }

    /**
     * @return array{user: User, farmer: Farmer}
     */
    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $farmer = $this->resolveFarmer($user);

            $farmerData = array_intersect_key($data, array_flip([
                'first_name', 'last_name', 'gender', 'telephone', 'address',
            ]));

            // `email` and `role` are absent from UpdateProfileRequest's rules,
            // so they cannot arrive here — changing either needs an admin.
            $userData = [];

            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if ($farmerData !== []) {
                $farmer->update($farmerData);
            }

            // Keep users.name, which the login response returns, in step with
            // the name columns the profile exposes.
            if (isset($farmerData['first_name']) || isset($farmerData['last_name'])) {
                $fresh = $farmer->fresh();
                $userData['name'] = trim("{$fresh->first_name} {$fresh->last_name}");
            }

            if ($userData !== []) {
                $user->update($userData);
            }

            $this->audit->log($farmer->id, 'profile_update', $farmer->id);

            return ['user' => $user->fresh(), 'farmer' => $farmer->fresh()];
        });
    }

    // -------------------------------------------------------------------------
    // UC-FAPI-14 — FCM device token
    // -------------------------------------------------------------------------

    public function registerDeviceToken(User $user, string $deviceToken): void
    {
        $farmer = $this->resolveFarmer($user);

        // Stored on the farmer profile, never on the user row, and never
        // logged or echoed back — it is a credential for that device.
        $farmer->update(['fcm_token' => $deviceToken]);

        $this->audit->log($farmer->id, 'device_token_registered', $farmer->id);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * A farmer User should always have a profile row, but accounts created
     * before the two paths were unified may not. Heal rather than fatal:
     * a missing profile would otherwise make the account unusable forever.
     */
    private function resolveFarmer(User $user): Farmer
    {
        $farmer = Farmer::where('user_id', $user->id)->first();

        if ($farmer) {
            return $farmer;
        }

        Log::warning('Farmer profile missing for farmer user; creating one.', [
            'user_id' => $user->id,
        ]);

        return $this->linker->linkOrCreate($user, [
            'status'         => $user->status === 'active' ? 'Active' : 'Inactive',
            'profile_status' => $user->status === 'active' ? 'active' : 'pending',
        ]);
    }

    /**
     * Mail must never be the reason an authentication action fails: the
     * transport is out of our control and the user-visible outcome (and, for
     * password reset, the anti-enumeration guarantee) must not depend on it.
     */
    private function sendQuietly(callable $send, string $description): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Failed to send {$description}.", ['exception' => $e->getMessage()]);
        }
    }
}

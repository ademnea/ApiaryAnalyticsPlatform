<?php

namespace App\Services\Farmer;

use App\Mail\Farmer\FarmerPasswordReset;
use App\Mail\Farmer\NewRegistrationForReview;
use App\Mail\Farmer\RegistrationPending;
use App\Models\Farmer;
use App\Models\User;
use App\Services\Notifications\SmsNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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
    private const PASSWORD_RESET_CODE_TTL_MINUTES = 10;
    private const PASSWORD_RESET_CODE_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly FarmerProfileLinker $linker,
        private readonly FarmerAuditService $audit,
        private readonly SmsNotificationService $sms,
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

        $this->sendQuietly(
            fn () => $this->sms->sendToFarmer(
                $result['farmer'],
                'registration_pending',
                'AdEMNEA registration received',
                'Your AdEMNEA farmer registration has been received and is awaiting administrator approval.'
            ),
            'farmer registration SMS'
        );

        $this->sendQuietly(
            fn () => $this->sms->sendToAdmins(
                'registration_pending',
                'New farmer registration',
                "A farmer registration is awaiting review: {$result['user']->name} ({$result['user']->email})."
            ),
            'admin registration SMS'
        );

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
     * Farmer mobile resets use a short-lived numeric code. Only its hash is
     * stored, and successful use removes it from the shared reset-token table.
     */
    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->where('role', 'farmer')->first();

        if (! $user) {
            return;
        }

        $requestKey = 'farmer-password-reset-request:'.hash('sha256', mb_strtolower($user->email));

        if (RateLimiter::tooManyAttempts($requestKey, 1)) {
            return;
        }

        RateLimiter::hit($requestKey, (int) config('auth.passwords.users.throttle', 60));

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table(config('auth.passwords.users.table'))->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        $this->notifyPasswordReset($user, $code);
    }

    private function notifyPasswordReset(User $user, string $code): void
    {
        $this->sendQuietly(
            fn () => Mail::to($user->email)->send(new FarmerPasswordReset($user, $code)),
            'farmer password reset email'
        );

        $this->sendQuietly(function () use ($user, $code) {
            $this->sms->sendToFarmer(
                $this->resolveFarmer($user),
                'password_reset',
                'AdEMNEA password reset',
                "Your AdEMNEA password reset code is {$code}. It expires in 10 minutes."
            );
        }, 'farmer password reset SMS');
    }

    public function resetPassword(array $data): bool
    {
        $email = mb_strtolower($data['email']);
        $attemptKey = 'farmer-password-reset:'.hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($attemptKey, self::PASSWORD_RESET_CODE_MAX_ATTEMPTS)) {
            return false;
        }

        RateLimiter::hit($attemptKey, self::PASSWORD_RESET_CODE_TTL_MINUTES * 60);

        $reset = DB::transaction(function () use ($data) {
            $user = User::where('email', $data['email'])
                ->where('role', 'farmer')
                ->lockForUpdate()
                ->first();

            $reset = DB::table(config('auth.passwords.users.table'))
                ->where('email', $user?->email ?? $data['email'])
                ->lockForUpdate()
                ->first();

            if (
                ! $user
                || ! $reset
                || ! $reset->created_at
                || Carbon::parse($reset->created_at)->addMinutes(self::PASSWORD_RESET_CODE_TTL_MINUTES)->isPast()
                || ! Hash::check($data['token'], $reset->token)
            ) {
                return false;
            }

            // The `hashed` cast on User hashes this; do not pre-hash.
            $user->forceFill(['password' => $data['password']])->save();
            DB::table(config('auth.passwords.users.table'))->where('email', $user->email)->delete();

            // Deliberately do not revoke Sanctum tokens: existing sessions
            // survive a password reset.
            return true;
        });

        if ($reset) {
            RateLimiter::clear($attemptKey);
        }

        return $reset;
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

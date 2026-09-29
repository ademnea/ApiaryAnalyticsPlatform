<?php

namespace App\Services\ApiaryManagement;

use App\Mail\Farmer\AccountApproved;
use App\Mail\Farmer\RegistrationRejected;
use App\Models\Farmer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * UC-FAPI-01, admin approval and rejection.
 *
 * Approval state is recorded in two places because the two sides of the
 * system ask different questions of it:
 *
 *   farmers.profile_status  drives the admin pending queue
 *                           (pending | active | incomplete)
 *   users.status            gates login
 *                           (pending | active | rejected | suspended)
 *
 * Keeping them in step here is what makes approval actually work: before
 * this existed, approving a farmer flipped only profile_status, so the
 * account stayed `pending` and the farmer still could not sign in.
 *
 * A farmer created directly in the admin registry has no linked User. That
 * is legitimate — they simply have no way to sign in yet — so the user
 * block is skipped rather than treated as an error.
 */
class FarmerApprovalService
{
    public function approve(Farmer $farmer, string $role = 'farmer'): Farmer
    {
        DB::transaction(function () use ($farmer, $role) {
            $farmer->update([
                'profile_status' => 'active',
                'status'         => 'Active',
                'is_active'      => true,
            ]);

            if ($user = $farmer->user) {
                $user->update([
                    'status'    => 'active',
                    'role'      => 'farmer',
                    'is_active' => true,
                ]);

                // farmer-write is additive, never a replacement: an elevated
                // farmer keeps the base role so route middleware and the
                // base permissions still apply.
                $user->syncRoles(
                    $role === 'farmer-write' ? ['farmer', 'farmer-write'] : ['farmer']
                );
            }
        });

        $this->notify(
            fn () => Mail::to($this->contactAddress($farmer))->send(new AccountApproved($farmer)),
            $farmer,
            'approval'
        );

        return $farmer->refresh();
    }

    public function reject(Farmer $farmer, ?string $reason = null): Farmer
    {
        DB::transaction(function () use ($farmer) {
            // 'incomplete' rather than a new 'rejected' value: profile_status
            // is documented as active|pending|incomplete and the admin tests
            // assert that vocabulary. users.status carries the real decision.
            $farmer->update([
                'profile_status' => 'incomplete',
                'status'         => 'Inactive',
                'is_active'      => false,
            ]);

            if ($user = $farmer->user) {
                $user->update([
                    'status'    => 'rejected',
                    'is_active' => false,
                ]);

                // Revoke any session already issued to this account.
                $user->tokens()->delete();
            }
        });

        $this->notify(
            fn () => Mail::to($this->contactAddress($farmer))->send(new RegistrationRejected($farmer, $reason)),
            $farmer,
            'rejection'
        );

        return $farmer->refresh();
    }

    private function contactAddress(Farmer $farmer): ?string
    {
        return $farmer->email ?: $farmer->user?->email;
    }

    /**
     * The approval decision is already committed; a mail failure must not
     * undo it or surface as an error to the administrator.
     */
    private function notify(callable $send, Farmer $farmer, string $kind): void
    {
        if (! $this->contactAddress($farmer)) {
            return;
        }

        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Failed to send farmer {$kind} notification.", [
                'farmer_id' => $farmer->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}

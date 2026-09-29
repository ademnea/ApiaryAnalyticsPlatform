<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\Hive;
use App\Models\HiveWeight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * UC-FAPI-15, 16: alert creation and retrieval.
 *
 * Delivery lives in NotificationDispatchService. Keeping the FCM and SMS
 * calls here as well made the two classes mutually dependent.
 *
 * This file was previously unparseable — a bad merge had left a duplicate
 * `use Log` import, two `createAlert()` declarations, a `markRead()` body
 * spliced into the middle of sendPushNotification(), and try blocks with no
 * opening statement. It has been reconstructed.
 *
 * Alerts are addressed by farmers.id. Callers must pass a farmer id, never
 * the id of the User that authenticated the request.
 */
class AlertService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications
    ) {}

    // -------------------------------------------------------------------------
    // Retrieval (UC-FAPI-16)
    // -------------------------------------------------------------------------

    public function fetchForFarmer(int $farmerId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('farmer_id', $farmerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function markRead(Alert $alert, int $farmerId): bool
    {
        if ($alert->farmer_id !== $farmerId) {
            return false;
        }

        if (! $alert->is_read) {
            $alert->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Creation (UC-FAPI-15)
    // -------------------------------------------------------------------------

    public function createAlert(int $farmerId, int $hiveId, string $type, string $message): ?Alert
    {
        // Malfunction alerts are exempt from the cooldown: a hardware fault
        // must not be suppressed because a similar alert fired recently.
        if ($type !== 'malfunction' && $this->isWithinCooldown($hiveId, $type)) {
            return null;
        }

        $alert = DB::transaction(fn () => Alert::create([
            'farmer_id'  => $farmerId,
            'hive_id'    => $hiveId,
            'type'       => $type,
            'message'    => $message,
            'is_read'    => false,
            'created_at' => now(),
        ]));

        $this->notifications->dispatch($alert);

        return $alert;
    }

    public function isWithinCooldown(int $hiveId, string $type): bool
    {
        return Alert::where('hive_id', $hiveId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }

    /**
     * Hourly sweep driven by App\Jobs\CheckFeedAlerts.
     */
    public function evaluateThresholds(): void
    {
        // Ownership runs hive -> apiary -> farmer, the same path the
        // farmer-facing API scopes by. farmers.status is an enum of
        // Active/Inactive/Suspended — capitalised.
        $hives = Hive::whereHas('apiary.farmer', fn ($q) => $q->where('status', 'Active'))
            ->with('apiary.farmer')
            ->get();

        foreach ($hives as $hive) {
            $farmer = $hive->apiary->farmer ?? null;

            if (! $farmer) {
                continue;
            }

            $this->checkFeedRequired($hive, $farmer->id);
        }
    }

    private function checkFeedRequired(Hive $hive, int $farmerId): void
    {
        $threshold = (float) AlertThreshold::getForHive($hive->id, 'feed_required_weight_kg', 15);

        $latest = HiveWeight::where('hive_id', $hive->id)
            ->orderByDesc('created_at')
            ->first();

        if (! $latest) {
            return;
        }

        if ((float) $latest->weight_kg <= $threshold) {
            $this->createAlert(
                $farmerId,
                $hive->id,
                'feed_required',
                "Hive '{$hive->name}' weight is {$latest->weight_kg} kg — below the {$threshold} kg threshold. Feeding required."
            );
        }
    }
}

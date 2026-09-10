<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AlertService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications
    ) {}

    public function fetchForFarmer(int $farmerId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('farmer_id', $farmerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getAlerts(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return $this->fetchForFarmer($farmer->id, $perPage);
    }

    public function markRead(Alert $alert, int $farmerId): bool
    {
        if ($alert->farmer_id !== $farmerId) {
            return false;
        }

        if (!$alert->is_read) {
            $alert->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return true;
    }

    public function markAsRead(Farmer $farmer, int $alertId): Alert
    {
        $alert = Alert::where('id', $alertId)
            ->where('farmer_id', $farmer->id)
            ->firstOrFail();

        $this->markRead($alert, $farmer->id);

        return $alert->fresh();
    }

    public function evaluateThresholds(): void
    {
        $hives = Hive::whereHas('apiary.farmer', function ($q) {
            $q->where('status', 'active');
        })->with('apiary.farmer')->get();

        foreach ($hives as $hive) {
            $farmer = $hive->apiary->farmer ?? null;
            if (!$farmer) {
                continue;
            }

            $this->checkFeedRequired($hive, $farmer->id);
        }
    }

    public function createAlert(int $farmerId, int $hiveId, string $type, string $message): ?Alert
    {
        if ($type !== 'malfunction' && $this->isWithinCooldown($hiveId, $type)) {
            return null;
        }

        $alert = DB::transaction(function () use ($farmerId, $hiveId, $type, $message) {
            return Alert::create([
                'farmer_id'  => $farmerId,
                'hive_id'    => $hiveId,
                'type'       => $type,
                'message'    => $message,
                'is_read'    => false,
                'created_at' => now(),
            ]);
        });

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

    private function checkFeedRequired(Hive $hive, int $farmerId): void
    {
        $threshold = (float) AlertThreshold::getForHive($hive->id, 'feed_required_weight_kg', 15);

        $latest = HiveWeight::where('hive_id', $hive->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latest) {
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
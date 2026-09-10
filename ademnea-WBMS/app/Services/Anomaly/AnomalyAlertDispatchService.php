<?php

namespace App\Services\Anomaly;

use App\Models\Alert;
use App\Models\SensorAnomaly;
use App\Services\Farmer\NotificationDispatchService;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the target farmer via $anomaly->hive->apiary->farmer (the Farm
 * model has been retired — see IotDeviceIdentificationService), checks
 * Alert::cooldownMinutesFor(), writes Alert::create() directly, then
 * delegates delivery to NotificationDispatchService. Deliberately does not
 * depend on App\Services\Farmer\AlertService.
 */
class AnomalyAlertDispatchService
{
    public function __construct(private readonly NotificationDispatchService $notifications)
    {
    }

    public function dispatch(SensorAnomaly $anomaly): ?Alert
    {
        $farmer = $anomaly->hive?->apiary?->farmer;

        if (! $farmer) {
            Log::info('Anomaly dispatch skipped — no resolvable farmer', [
                'sensor_anomaly_id' => $anomaly->id,
                'hive_id' => $anomaly->hive_id,
            ]);

            return null;
        }

        $minutes = (new Alert())->cooldownMinutesFor($anomaly->anomaly_type);

        if ($minutes !== null && $this->isWithinCooldown($anomaly, $minutes)) {
            return null;
        }

        $alert = Alert::create([
            'farmer_id' => $farmer->id,
            'hive_id' => $anomaly->hive_id,
            'source_anomaly_id' => $anomaly->id,
            'type' => $this->alertTypeFor($anomaly),
            'message' => $this->messageFor($anomaly),
            'is_read' => false,
            'created_at' => now(),
        ]);

        $anomaly->update(['alerted' => true, 'alerted_at' => now()]);

        $this->notifications->dispatch($alert);

        return $alert;
    }

    /**
     * alerts.type is a fixed enum (feed_required|malfunction|critical_event|
     * low_battery|weak_signal|data_anomaly) — it does not have a slot for
     * every anomaly_type this module produces. low_battery/weak_signal
     * already have a matching enum value (pre-reserved for this module,
     * per the SDD's extension-points table); everything else collapses
     * into the generic data_anomaly bucket. The granular anomaly_type is
     * never lost — it's still on sensor_anomalies, reachable from the
     * Alert via source_anomaly_id.
     */
    private function alertTypeFor(SensorAnomaly $anomaly): string
    {
        return match ($anomaly->anomaly_type) {
            'low_battery', 'weak_signal' => $anomaly->anomaly_type,
            default => 'data_anomaly',
        };
    }

    /**
     * Cooldown is scoped by the granular anomaly_type, not by the
     * collapsed alerts.type enum value — otherwise unrelated anomaly types
     * that both map to 'data_anomaly' would incorrectly suppress each
     * other's alerts on the same hive. Joins through source_anomaly_id.
     */
    private function isWithinCooldown(SensorAnomaly $anomaly, int $minutes): bool
    {
        return Alert::where('hive_id', $anomaly->hive_id)
            ->whereHas('sourceAnomaly', fn ($q) => $q->where('anomaly_type', $anomaly->anomaly_type))
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    private function messageFor(SensorAnomaly $anomaly): string
    {
        $value = collect($anomaly->record_value)
            ->map(fn ($v, $k) => "{$k}={$v}")
            ->implode(', ');

        return "Anomaly detected on hive #{$anomaly->hive_id}: {$anomaly->anomaly_type} ({$value}).";
    }
}

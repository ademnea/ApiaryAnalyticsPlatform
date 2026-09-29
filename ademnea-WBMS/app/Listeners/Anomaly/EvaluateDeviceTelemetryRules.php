<?php

namespace App\Listeners\Anomaly;

use App\Events\DeviceTelemetryReceived;
use App\Models\IotDeviceTelemetryHistory;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use App\Services\Anomaly\RulesEngine\DeviceTelemetryRuleEvaluator;

class EvaluateDeviceTelemetryRules
{
    public function __construct(
        private readonly DeviceTelemetryRuleEvaluator $telemetryRule,
        private readonly AnomalyAlertDispatchService $dispatchService,
    ) {
    }

    public function handle(DeviceTelemetryReceived $event): void
    {
        $telemetry = $event->device->telemetry()->first();

        if (! $telemetry) {
            return;
        }

        // History records this heartbeat's own values at the time it was
        // sent — which, for an out-of-order heartbeat, differ from the
        // device's current telemetry row.
        $reading = $event->reading ?: $telemetry->only([
            'battery_level', 'signal_strength', 'uptime_seconds', 'cpu_usage',
            'storage_usage', 'reboot_count', 'sensor_read_success_rate', 'error_codes',
        ]);

        IotDeviceTelemetryHistory::create([
            'device_id' => $event->device->id,
            'battery_level' => $reading['battery_level'] ?? null,
            'signal_strength' => $reading['signal_strength'] ?? null,
            'uptime_seconds' => $reading['uptime_seconds'] ?? null,
            'cpu_usage' => $reading['cpu_usage'] ?? null,
            'storage_usage' => $reading['storage_usage'] ?? null,
            'reboot_count' => $reading['reboot_count'] ?? null,
            'sensor_read_success_rate' => $reading['sensor_read_success_rate'] ?? null,
            'error_codes' => $reading['error_codes'] ?? null,
            'recorded_at' => $event->recordedAt ?? $telemetry->last_heartbeat_at ?? now(),
            'created_at' => now(),
        ]);

        // Rules judge the device's current state; a stale heartbeat must not
        // open or auto-resolve incidents based on superseded values.
        if (! $event->isLatest) {
            return;
        }

        // Alert only when an incident is first opened — repeats of an
        // already-open incident just bump its occurrence count.
        foreach ($this->telemetryRule->evaluateAll($telemetry, $event->device) as $anomaly) {
            if ($anomaly->wasRecentlyCreated) {
                $this->dispatchService->dispatch($anomaly);
            }
        }
    }
}

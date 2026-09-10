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
        $telemetry = $event->device->telemetry;

        if (! $telemetry) {
            return;
        }

        IotDeviceTelemetryHistory::create([
            'device_id' => $event->device->id,
            'battery_level' => $telemetry->battery_level,
            'signal_strength' => $telemetry->signal_strength,
            'uptime_seconds' => $telemetry->uptime_seconds,
            'cpu_usage' => $telemetry->cpu_usage,
            'storage_usage' => $telemetry->storage_usage,
            'reboot_count' => $telemetry->reboot_count,
            'sensor_read_success_rate' => $telemetry->sensor_read_success_rate,
            'error_codes' => $telemetry->error_codes,
            'recorded_at' => $telemetry->last_heartbeat_at ?? now(),
            'created_at' => now(),
        ]);

        $anomaly = $this->telemetryRule->evaluate($telemetry, $event->device);

        if ($anomaly) {
            $this->dispatchService->dispatch($anomaly);
        }
    }
}

<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\SensorAnomaly;

/**
 * Battery/signal/reboot/storage threshold checks against a device's
 * just-written telemetry row. Returns only the single highest-severity
 * match (critical battery > low battery > weak signal > storage > reboot
 * loop) to keep the evaluate(): ?SensorAnomaly contract intact.
 */
class DeviceTelemetryRuleEvaluator
{
    public function evaluate(IotDeviceTelemetry $telemetry, IotDevice $device): ?SensorAnomaly
    {
        $hiveId = $device->hive_id;

        if ($telemetry->battery_level !== null) {
            $critical = (float) AlertThreshold::getForHive($hiveId, 'critical_battery_pct', 5);

            if ($telemetry->battery_level <= $critical) {
                return $this->record($device, 'critical_battery', ['battery_level' => $telemetry->battery_level]);
            }

            $low = (float) AlertThreshold::getForHive($hiveId, 'low_battery_pct', 20);

            if ($telemetry->battery_level <= $low) {
                return $this->record($device, 'low_battery', ['battery_level' => $telemetry->battery_level]);
            }
        }

        if ($telemetry->signal_strength !== null) {
            $weakSignal = (float) AlertThreshold::getForHive($hiveId, 'weak_signal_rssi_dbm', -85);

            if ($telemetry->signal_strength <= $weakSignal) {
                return $this->record($device, 'weak_signal', ['signal_strength' => $telemetry->signal_strength]);
            }
        }

        if ($telemetry->storage_usage !== null) {
            $storageFull = (float) AlertThreshold::getForHive($hiveId, 'storage_full_pct', 90);

            if ($telemetry->storage_usage >= $storageFull) {
                return $this->record($device, 'storage_full', ['storage_usage' => $telemetry->storage_usage]);
            }
        }

        $rebootAnomaly = $this->checkRebootLoop($device, $hiveId);

        if ($rebootAnomaly) {
            return $rebootAnomaly;
        }

        return null;
    }

    private function checkRebootLoop(IotDevice $device, ?int $hiveId): ?SensorAnomaly
    {
        $threshold = (int) AlertThreshold::getForHive($hiveId, 'reboot_loop_count_per_hour', 3);

        $windowRows = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subHour())
            ->orderBy('recorded_at')
            ->get(['reboot_count', 'recorded_at']);

        if ($windowRows->count() < 2) {
            return null;
        }

        $delta = $windowRows->last()->reboot_count - $windowRows->first()->reboot_count;

        if ($delta >= $threshold) {
            return $this->record($device, 'reboot_loop', ['reboot_count_delta' => $delta]);
        }

        return null;
    }

    private function record(IotDevice $device, string $anomalyType, array $recordValue): SensorAnomaly
    {
        return SensorAnomaly::create([
            'device_id' => $device->id,
            'hive_id' => $device->hive_id,
            'sensor_type' => 'telemetry',
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => $recordValue,
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }
}

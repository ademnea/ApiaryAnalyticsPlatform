<?php

namespace App\Services;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Classifies a single device's live condition (online / warning / offline,
 * battery, signal) from its latest telemetry row, using the same
 * thresholds everywhere a device's health is shown — the fleet-wide
 * Anomaly Dashboard and the Device Registry alike.
 */
class IotDeviceHealthEvaluator
{
    public function evaluate(IotDevice $device, ?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();

        $silenceThresholdMinutes = (int) AlertThreshold::get('device_offline_silence_minutes', 120);
        $criticalBatteryPct = (float) AlertThreshold::get('critical_battery_pct', 5);
        $lowBatteryPct = (float) AlertThreshold::get('low_battery_pct', 20);
        $weakSignalRssi = (float) AlertThreshold::get('weak_signal_rssi_dbm', -85);

        $telemetry = $device->telemetry;

        $lastContact = collect([
            $telemetry?->last_heartbeat_at,
            $telemetry?->last_data_received_at,
        ])->filter()->max();

        $minutesSinceContact = $lastContact ? $lastContact->diffInMinutes($now) : null;
        $isOffline = $minutesSinceContact === null || $minutesSinceContact > $silenceThresholdMinutes;

        $isLowBattery = $telemetry?->battery_level !== null && $telemetry->battery_level <= $lowBatteryPct;
        $isCriticalBattery = $telemetry?->battery_level !== null && $telemetry->battery_level <= $criticalBatteryPct;
        $isWeakSignal = $telemetry?->signal_strength !== null && $telemetry->signal_strength <= $weakSignalRssi;

        $status = match (true) {
            $isOffline => 'offline',
            $isLowBattery || $isWeakSignal => 'warning',
            default => 'online',
        };

        return [
            'status' => $status,
            'last_contact' => $lastContact,
            'never_reported' => $lastContact === null,
            'minutes_since_contact' => $minutesSinceContact,
            'battery_level' => $telemetry?->battery_level,
            'signal_strength' => $telemetry?->signal_strength,
            'is_low_battery' => $isLowBattery,
            'is_critical_battery' => $isCriticalBattery,
            'is_weak_signal' => $isWeakSignal,
        ];
    }
}

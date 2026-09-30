<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\SensorAnomaly;
use Illuminate\Support\Carbon;

/**
 * Battery/signal/reboot/storage/firmware checks against a device's
 * just-written telemetry row. Weak signal and reboot loop look back through
 * heartbeat history: the signal must stay weak for
 * weak_signal_sustained_minutes (REQ-F-IOT-08), and reboots are counted
 * over the last hour.
 *
 * Every violated condition is its own incident (a device can have low
 * battery AND weak signal at once). Conditions that were evaluated and are
 * no longer violated get their open incident auto-resolved. device_offline
 * is never touched here — CheckDeviceHealth owns that incident.
 */
class DeviceTelemetryRuleEvaluator
{
    /** Severity order, highest first — evaluate() returns the first match. */
    private const PRIORITY = ['critical_battery', 'low_battery', 'weak_signal', 'storage_full', 'reboot_loop', 'firmware_outdated'];

    /**
     * Records/touches all current violations, auto-resolves recovered ones,
     * and returns the single highest-severity incident (or null if healthy).
     */
    public function evaluate(IotDeviceTelemetry $telemetry, IotDevice $device): ?SensorAnomaly
    {
        return $this->evaluateAll($telemetry, $device)[0] ?? null;
    }

    /**
     * @return array<int, SensorAnomaly> incidents for every violated condition, highest severity first
     */
    public function evaluateAll(IotDeviceTelemetry $telemetry, IotDevice $device): array
    {
        [$violations, $evaluatedTypes] = $this->violations($telemetry, $device);

        SensorAnomaly::autoResolve(
            $device->id,
            SensorAnomaly::DEVICE_SENSOR_TYPE,
            array_values(array_diff($evaluatedTypes, array_keys($violations))),
        );

        $incidents = [];

        foreach (self::PRIORITY as $type) {
            if (isset($violations[$type])) {
                $incidents[] = $this->record($device, $type, $violations[$type]);
            }
        }

        return $incidents;
    }

    /**
     * @return array{0: array<string, array>, 1: array<int, string>} [type => record_value] violations, and every type that was actually checked
     */
    private function violations(IotDeviceTelemetry $telemetry, IotDevice $device): array
    {
        $hiveId = $device->hive_id;
        $violations = [];
        $evaluated = [];

        if ($telemetry->battery_level !== null) {
            $evaluated[] = 'critical_battery';
            $evaluated[] = 'low_battery';

            $critical = (float) $this->threshold($hiveId, 'critical_battery_pct', 5);
            $low = (float) $this->threshold($hiveId, 'low_battery_pct', 20);

            // Critical supersedes low — escalating closes the low_battery incident.
            if ($telemetry->battery_level <= $critical) {
                $violations['critical_battery'] = ['battery_level' => $telemetry->battery_level];
            } elseif ($telemetry->battery_level <= $low) {
                $violations['low_battery'] = ['battery_level' => $telemetry->battery_level];
            }
        }

        if ($telemetry->signal_strength !== null) {
            $weakSignal = (float) $this->threshold($hiveId, 'weak_signal_rssi_dbm', -85);

            if ($telemetry->signal_strength > $weakSignal) {
                $evaluated[] = 'weak_signal';
            } else {
                // Weak but not yet for long enough is neither a violation nor
                // a recovery: an open incident is left as it is.
                $weakMinutes = $this->weakSignalMinutes($device, $weakSignal);

                if ($weakMinutes !== null && $weakMinutes >= (int) $this->threshold($hiveId, 'weak_signal_sustained_minutes', 30)) {
                    $evaluated[] = 'weak_signal';
                    $violations['weak_signal'] = ['signal_strength' => $telemetry->signal_strength, 'weak_for_minutes' => $weakMinutes];
                }
            }
        }

        if ($telemetry->storage_usage !== null) {
            $evaluated[] = 'storage_full';
            $storageFull = (float) $this->threshold($hiveId, 'storage_full_pct', 90);

            if ($telemetry->storage_usage >= $storageFull) {
                $violations['storage_full'] = ['storage_usage' => $telemetry->storage_usage];
            }
        }

        $evaluated[] = 'reboot_loop';
        $rebootDelta = $this->rebootDelta($device);

        if ($rebootDelta !== null && $rebootDelta >= (int) $this->threshold($hiveId, 'reboot_loop_count_per_hour', 3)) {
            $violations['reboot_loop'] = ['reboot_count_delta' => $rebootDelta];
        }

        // Off until an admin sets latest_firmware_version; the seeded "0"
        // matches nothing, since no version is older than 0.
        $latestFirmware = trim((string) $this->threshold($hiveId, 'latest_firmware_version', ''));
        $firmware = $telemetry->firmware_version ?: $device->firmware_version;

        if ($latestFirmware !== '' && filled($firmware)) {
            $evaluated[] = 'firmware_outdated';

            if (version_compare(ltrim($firmware, 'vV'), ltrim($latestFirmware, 'vV'), '<')) {
                $violations['firmware_outdated'] = ['firmware_version' => $firmware, 'latest_firmware_version' => $latestFirmware];
            }
        }

        return [$violations, $evaluated];
    }

    /**
     * How long the signal has been at or below $weakSignal without a break:
     * the span of heartbeat history from the first weak reading after the
     * last good one to the latest. Measured between readings, not to now(),
     * so a single weak heartbeat that arrives late doesn't count as a long
     * spell. The current heartbeat is already in history.
     */
    private function weakSignalMinutes(IotDevice $device, float $weakSignal): ?int
    {
        $lastGoodAt = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('signal_strength', '>', $weakSignal)
            ->max('recorded_at');

        $spell = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->whereNotNull('signal_strength')
            ->when($lastGoodAt, fn ($q) => $q->where('recorded_at', '>', $lastGoodAt))
            ->toBase()
            ->selectRaw('MIN(recorded_at) as first_weak_at, MAX(recorded_at) as last_weak_at')
            ->first();

        if (! $spell?->first_weak_at) {
            return null;
        }

        return (int) floor(Carbon::parse($spell->first_weak_at)->diffInMinutes(Carbon::parse($spell->last_weak_at), true));
    }

    /** Per-hive override when the device is assigned, global value otherwise. */
    private function threshold(?int $hiveId, string $key, mixed $default): mixed
    {
        return $hiveId !== null
            ? AlertThreshold::getForHive($hiveId, $key, $default)
            : AlertThreshold::get($key, $default);
    }

    /** Reboots observed in the last hour, or null when there isn't enough history to tell. */
    private function rebootDelta(IotDevice $device): ?int
    {
        $windowRows = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subHour())
            ->orderBy('recorded_at')
            ->get(['reboot_count', 'recorded_at']);

        if ($windowRows->count() < 2) {
            return null;
        }

        return (int) $windowRows->last()->reboot_count - (int) $windowRows->first()->reboot_count;
    }

    private function record(IotDevice $device, string $anomalyType, array $recordValue): SensorAnomaly
    {
        return SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $device->hive_id,
            'sensor_type' => SensorAnomaly::DEVICE_SENSOR_TYPE,
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => $recordValue,
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }
}

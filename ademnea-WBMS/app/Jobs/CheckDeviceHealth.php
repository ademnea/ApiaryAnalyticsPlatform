<?php

namespace App\Jobs;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SRS UC-IOT-03 / REQ-F-IOT-03: server-side gap/interval detection —
 * submission_delay once a device has been silent for more than
 * submission_delay_multiplier × its expected interval, device_offline once
 * it passes device_offline_silence_minutes. Not
 * scoped to hive_id non-null — an unassigned device can still be
 * offline/low-battery. Deliberately does not re-run
 * DeviceTelemetryRuleEvaluator's battery/signal/storage checks here — this
 * job's scope is gap/interval detection only; those checks run at
 * ingestion time via EvaluateDeviceTelemetryRules.
 */
class CheckDeviceHealth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Connectivity-gap incidents this job owns. */
    private const GAP_TYPES = ['device_offline', 'submission_delay'];

    public function handle(AnomalyAlertDispatchService $dispatchService): void
    {
        $devices = IotDevice::where('active_flag', true)->with('telemetry')->get();

        foreach ($devices as $device) {
            $this->checkDevice($device, $dispatchService);
        }

        Log::info('Device health check job executed.', ['devices_checked' => $devices->count()]);
    }

    private function checkDevice(IotDevice $device, AnomalyAlertDispatchService $dispatchService): void
    {
        $telemetry = $device->telemetry;

        if (! $telemetry) {
            return;
        }

        $lastContact = collect([$telemetry->last_heartbeat_at, $telemetry->last_data_received_at])
            ->filter()
            ->max();

        if (! $lastContact) {
            return;
        }

        // Clamped at 0: a device clock ahead of the server would otherwise
        // produce a negative gap (and data_gap_minutes is unsigned).
        $gapMinutes = max(0, (int) floor($lastContact->diffInMinutes(now())));

        $silenceThreshold = (int) $this->threshold($device, 'device_offline_silence_minutes', 120);
        $lateThreshold = max(1, (int) $device->expected_interval_minutes)
            * max(1, (float) $this->threshold($device, 'submission_delay_multiplier', 3));

        $telemetry->update([
            'data_gap_minutes' => $gapMinutes,
            'submission_interval_actual' => $this->observedInterval($device),
        ]);

        $condition = match (true) {
            $gapMinutes >= $silenceThreshold => 'device_offline',
            $gapMinutes >= $lateThreshold => 'submission_delay',
            default => null,
        };

        // Offline supersedes late: whichever condition no longer holds is
        // closed, so a device never has both incidents open at once.
        SensorAnomaly::autoResolve(
            $device->id,
            SensorAnomaly::DEVICE_SENSOR_TYPE,
            array_values(array_diff(self::GAP_TYPES, [$condition])),
        );

        if ($condition === null) {
            return;
        }

        // Repeat checks while the condition persists only touch the open
        // incident (its last_record_value tracks the growing gap), so the
        // farmer is alerted once per incident — device_offline has no alert
        // cooldown, this is the recovery-gated mechanism SDD §4.4.9 flags.
        $anomaly = SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $this->openIncidentHiveId($device, $condition),
            'sensor_type' => SensorAnomaly::DEVICE_SENSOR_TYPE,
            'anomaly_type' => $condition,
            'anomaly_score' => 1.0,
            'record_value' => [
                'data_gap_minutes' => $gapMinutes,
                'expected_interval_minutes' => (int) $device->expected_interval_minutes,
            ],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);

        if ($anomaly->wasRecentlyCreated) {
            $dispatchService->dispatch($anomaly);
        }
    }

    /**
     * Keeps touching an open incident even if the device was reassigned to
     * another hive since it opened, instead of opening a duplicate.
     */
    private function openIncidentHiveId(IotDevice $device, string $anomalyType): ?int
    {
        $open = SensorAnomaly::query()
            ->open()
            ->where('device_id', $device->id)
            ->where('anomaly_type', $anomalyType)
            ->latest('id')
            ->first();

        return $open ? $open->hive_id : $device->hive_id;
    }

    private function threshold(IotDevice $device, string $key, mixed $default): mixed
    {
        return $device->hive_id
            ? AlertThreshold::getForHive($device->hive_id, $key, $default)
            : AlertThreshold::get($key, $default);
    }

    /** Minutes between the two most recent heartbeats, or null without enough history. */
    private function observedInterval(IotDevice $device): ?float
    {
        $lastTwo = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->limit(2)
            ->get();

        if ($lastTwo->count() < 2) {
            return null;
        }

        // Carbon 3 diffs are signed — newest minus older would be negative.
        return $lastTwo->last()->recorded_at->diffInMinutes($lastTwo->first()->recorded_at, true);
    }
}

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
 * SRS UC-IOT-03 / REQ-F-IOT-03: server-side gap/interval detection. Not
 * scoped to hive_id non-null — an unassigned device can still be
 * offline/low-battery. Deliberately does not re-run
 * DeviceTelemetryRuleEvaluator's battery/signal/storage checks here — this
 * job's scope is gap/interval detection only; those checks run at
 * ingestion time via EvaluateDeviceTelemetryRules.
 */
class CheckDeviceHealth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $gapMinutes = $lastContact->diffInMinutes(now());

        $silenceThreshold = $device->hive_id
            ? (int) AlertThreshold::getForHive($device->hive_id, 'device_offline_silence_minutes', 120)
            : (int) AlertThreshold::get('device_offline_silence_minutes', 120);

        $telemetry->update([
            'data_gap_minutes' => $gapMinutes,
            'submission_interval_actual' => $this->observedInterval($device),
        ]);

        $openAnomaly = SensorAnomaly::where('device_id', $device->id)
            ->where('anomaly_type', 'device_offline')
            ->where('resolved', false)
            ->first();

        if ($gapMinutes >= $silenceThreshold) {
            // Guard against re-flagging every 5 minutes while still offline —
            // device_offline has no alert cooldown (Alert::cooldownMinutesFor()
            // returns null for it); this unresolved-anomaly check is the
            // "handled separately" recovery-gated mechanism SDD §4.4.9 flags.
            if (! $openAnomaly) {
                $anomaly = SensorAnomaly::create([
                    'device_id' => $device->id,
                    'hive_id' => $device->hive_id,
                    'sensor_type' => 'telemetry',
                    'anomaly_type' => 'device_offline',
                    'anomaly_score' => 1.0,
                    'record_value' => ['data_gap_minutes' => $gapMinutes],
                    'detection_layer' => 'rules',
                    'detected_at' => now(),
                ]);

                $dispatchService->dispatch($anomaly);
            }

            return;
        }

        if ($openAnomaly) {
            $openAnomaly->update(['resolved' => true, 'resolved_at' => now()]);
        }
    }

    private function observedInterval(IotDevice $device): ?float
    {
        $lastTwo = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->limit(2)
            ->get();

        if ($lastTwo->count() < 2) {
            return null;
        }

        return $lastTwo->first()->recorded_at->diffInMinutes($lastTwo->last()->recorded_at);
    }
}

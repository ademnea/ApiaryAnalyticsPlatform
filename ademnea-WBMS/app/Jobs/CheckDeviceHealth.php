<?php

namespace App\Jobs;

use App\Models\AlertThreshold;
use App\Models\IotDevice;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SRS UC-IOT-03 / REQ-F-IOT-03: server-side gap and interval detection.
 *
 * - device_offline: no contact (heartbeat or data) for
 *   device_offline_silence_minutes.
 * - submission_delay: the median gap between the device's last
 *   SUBMISSIONS_FOR_MEDIAN readings is more than
 *   submission_interval_multiplier × its expected interval, i.e. it is
 *   reporting too slowly, even if it hasn't gone silent.
 *
 * A device coming back online closes its device_offline incident and
 * notifies staff (UC-IOT-12, Alternative Flow B). Not scoped to hive_id
 * non-null: an unassigned device can still go offline. Battery, signal and
 * storage are checked at ingestion by EvaluateDeviceTelemetryRules instead.
 */
class CheckDeviceHealth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Connectivity-gap incidents this job owns. */
    private const GAP_TYPES = ['device_offline', 'submission_delay'];

    /** The median is taken over the gaps between this many readings (10 gaps). */
    private const SUBMISSIONS_FOR_MEDIAN = 11;

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
        $medianInterval = $this->medianSubmissionInterval($device);
        $expectedInterval = max(1, (int) $device->expected_interval_minutes);

        $telemetry->update([
            'data_gap_minutes' => $gapMinutes,
            'submission_interval_actual' => $medianInterval,
        ]);

        $condition = match (true) {
            $gapMinutes >= (int) $this->threshold($device, 'device_offline_silence_minutes', 120) => 'device_offline',
            $medianInterval !== null
                && $medianInterval > $expectedInterval * (float) $this->threshold($device, 'submission_interval_multiplier', 2) => 'submission_delay',
            default => null,
        };

        // Offline supersedes late: whichever condition no longer holds is
        // closed, so a device never has both incidents open at once.
        $recovered = $condition === 'device_offline' ? collect() : SensorAnomaly::open()
            ->where('device_id', $device->id)
            ->where('anomaly_type', 'device_offline')
            ->get();

        SensorAnomaly::autoResolve(
            $device->id,
            SensorAnomaly::DEVICE_SENSOR_TYPE,
            array_values(array_diff(self::GAP_TYPES, [$condition])),
        );

        foreach ($recovered as $incident) {
            $dispatchService->dispatchRecovery($incident->fresh());
        }

        if ($condition === null) {
            return;
        }

        // Repeat checks while the condition persists only touch the open
        // incident (its last_record_value tracks the growing gap), so staff
        // are alerted once per incident — device_offline has no alert
        // cooldown, this is the recovery-gated mechanism SDD §4.4.9 flags.
        $anomaly = SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $this->openIncidentHiveId($device, $condition),
            'sensor_type' => SensorAnomaly::DEVICE_SENSOR_TYPE,
            'anomaly_type' => $condition,
            'anomaly_score' => 1.0,
            'record_value' => array_filter([
                'data_gap_minutes' => $gapMinutes,
                'median_interval_minutes' => $condition === 'submission_delay' ? $medianInterval : null,
                'expected_interval_minutes' => $expectedInterval,
            ], fn ($value) => $value !== null),
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

    /**
     * Median minutes between the device's most recent readings, per sensor
     * stream (each stream is one reading per reporting cycle), taking the
     * fastest stream as the device's rhythm. Uses recorded_at, when the
     * device took each reading, so a backlog upload doesn't look like a
     * burst. Null until some stream has enough readings.
     */
    private function medianSubmissionInterval(IotDevice $device): ?float
    {
        $medians = [];

        foreach (['temperature', 'humidity', 'co2', 'weight'] as $sensorType) {
            $times = SensorChannels::modelClassFor($sensorType)::where('device_id', $device->id)
                ->orderByDesc('recorded_at')
                ->limit(self::SUBMISSIONS_FOR_MEDIAN)
                ->pluck('recorded_at');

            if ($times->count() < self::SUBMISSIONS_FOR_MEDIAN) {
                continue;
            }

            $gaps = $times->sliding(2)
                ->map(fn ($pair) => Carbon::parse($pair->last())->diffInMinutes(Carbon::parse($pair->first()), true))
                ->sort()
                ->values();

            $middle = intdiv($gaps->count(), 2);
            $medians[] = $gaps->count() % 2 === 0
                ? ($gaps[$middle - 1] + $gaps[$middle]) / 2
                : $gaps[$middle];
        }

        return $medians === [] ? null : round(min($medians), 2);
    }
}

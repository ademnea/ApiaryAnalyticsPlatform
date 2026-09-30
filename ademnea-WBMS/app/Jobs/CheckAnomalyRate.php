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

/**
 * SRS REQ-F-IOT-17, "High Anomaly Rate": more than high_anomaly_rate_pct of
 * a device's readings in the last hour were flagged suspect. One bad reading
 * is a glitch; a fifth of them means the unit needs attention.
 *
 * Needs high_anomaly_rate_min_readings in the hour before it judges, so
 * one flagged reading out of three doesn't count as 33 %. The incident
 * closes itself once the rate drops back.
 */
class CheckAnomalyRate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const WINDOW_MINUTES = 60;

    public function handle(AnomalyAlertDispatchService $dispatchService): void
    {
        IotDevice::where('active_flag', true)->each(fn (IotDevice $device) => $this->checkDevice($device, $dispatchService));
    }

    private function checkDevice(IotDevice $device, AnomalyAlertDispatchService $dispatchService): void
    {
        [$readings, $flagged] = $this->countsInWindow($device);

        if ($readings < (int) $this->threshold($device, 'high_anomaly_rate_min_readings', 10)) {
            return;
        }

        $ratePct = round($flagged / $readings * 100, 1);

        if ($ratePct <= (float) $this->threshold($device, 'high_anomaly_rate_pct', 20)) {
            SensorAnomaly::autoResolve($device->id, SensorAnomaly::DEVICE_SENSOR_TYPE, ['high_anomaly_rate']);

            return;
        }

        $anomaly = SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $device->hive_id,
            'sensor_type' => SensorAnomaly::DEVICE_SENSOR_TYPE,
            'anomaly_type' => 'high_anomaly_rate',
            'anomaly_score' => 1.0,
            'record_value' => ['flagged_readings' => $flagged, 'readings' => $readings, 'rate_pct' => $ratePct],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);

        if ($anomaly->wasRecentlyCreated) {
            $dispatchService->dispatch($anomaly);
        }
    }

    /** @return array{0: int, 1: int} readings and suspect readings across all four sensor tables */
    private function countsInWindow(IotDevice $device): array
    {
        $readings = 0;
        $flagged = 0;

        foreach (['temperature', 'humidity', 'co2', 'weight'] as $sensorType) {
            $row = SensorChannels::modelClassFor($sensorType)::where('device_id', $device->id)
                ->where('recorded_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
                ->toBase()
                ->selectRaw('COUNT(*) as readings, COALESCE(SUM(suspect), 0) as flagged')
                ->first();

            $readings += (int) $row->readings;
            $flagged += (int) $row->flagged;
        }

        return [$readings, $flagged];
    }

    private function threshold(IotDevice $device, string $key, mixed $default): mixed
    {
        return $device->hive_id
            ? AlertThreshold::getForHive($device->hive_id, $key, $default)
            : AlertThreshold::get($key, $default);
    }
}

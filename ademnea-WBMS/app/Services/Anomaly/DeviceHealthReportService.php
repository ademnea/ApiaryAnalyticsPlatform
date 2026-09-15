<?php

namespace App\Services\Anomaly;

use App\Models\IotDevice;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\SensorAnomaly;
use App\Services\IotDeviceHealthEvaluator;

/**
 * SRS UC-IOT-10 (per-device detail): live health, 7-day battery/signal
 * trend, 24h submission rate and the device's anomaly incidents — rendered
 * on the device registry page.
 */
class DeviceHealthReportService
{
    public function __construct(private readonly IotDeviceHealthEvaluator $healthEvaluator)
    {
    }

    /**
     * @return array{health: array, anomalies: \Illuminate\Support\Collection, openAnomaliesCount: int, telemetryTrend: \Illuminate\Support\Collection, trendChart: array, submissionChart: array}
     */
    public function forDevice(IotDevice $device): array
    {
        $device->loadMissing('telemetry', 'hive');

        // Both device issues and hive-condition anomalies raised by this device.
        $anomalies = SensorAnomaly::where('device_id', $device->id)
            ->orderBy('resolved')
            ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
            ->limit(20)
            ->get();

        $telemetryTrend = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->orderBy('recorded_at')
            ->get(['battery_level', 'signal_strength', 'recorded_at']);

        return [
            'health' => $this->healthEvaluator->evaluate($device),
            'anomalies' => $anomalies,
            'openAnomaliesCount' => SensorAnomaly::where('device_id', $device->id)->open()->count(),
            'telemetryTrend' => $telemetryTrend,
            'trendChart' => [
                'labels' => $telemetryTrend->map(fn ($row) => $row->recorded_at->format('M j, H:i'))->values(),
                'battery' => $telemetryTrend->map(fn ($row) => $row->battery_level)->values(),
                'signal' => $telemetryTrend->map(fn ($row) => $row->signal_strength)->values(),
            ],
            'submissionChart' => $this->submissionChart($device),
        ];
    }

    /** Heartbeats received per hour over the last 24h, against what the expected interval implies. */
    private function submissionChart(IotDevice $device): array
    {
        $hourStart = now()->startOfHour()->subHours(23);

        $perHour = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('recorded_at', '>=', $hourStart)
            ->pluck('recorded_at')
            ->countBy(fn ($recordedAt) => $recordedAt->copy()->startOfHour()->format('Y-m-d H'));

        $hours = collect(range(0, 23))->map(fn ($i) => $hourStart->copy()->addHours($i));

        return [
            'labels' => $hours->map(fn ($hour) => $hour->format('H:00'))->values(),
            'received' => $hours->map(fn ($hour) => $perHour->get($hour->format('Y-m-d H'), 0))->values(),
            'expected' => round(60 / max(1, (int) $device->expected_interval_minutes), 1),
        ];
    }
}

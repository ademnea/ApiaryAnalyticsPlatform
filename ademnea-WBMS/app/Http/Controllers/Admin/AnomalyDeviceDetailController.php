<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\SensorAnomaly;
use Illuminate\View\View;

/**
 * SRS UC-IOT-10: last-20-anomalies and battery/signal trend for one device.
 */
class AnomalyDeviceDetailController extends Controller
{
    public function show(IotDevice $device): View
    {
        $device->load('telemetry', 'hive');

        $anomalies = SensorAnomaly::where('device_id', $device->id)
            ->orderByDesc('detected_at')
            ->limit(20)
            ->get();

        $telemetryTrend = IotDeviceTelemetryHistory::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->orderBy('recorded_at')
            ->get(['battery_level', 'signal_strength', 'recorded_at']);

        $trendChart = [
            'labels' => $telemetryTrend->map(fn ($row) => $row->recorded_at->format('M j, H:i'))->values(),
            'battery' => $telemetryTrend->map(fn ($row) => $row->battery_level)->values(),
            'signal' => $telemetryTrend->map(fn ($row) => $row->signal_strength)->values(),
        ];

        return view('admin.anomaly.devices.show', compact('device', 'anomalies', 'telemetryTrend', 'trendChart'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensorAnomaly;
use Illuminate\View\View;

/**
 * SRS UC-IOT-09: fleet-wide anomaly overview.
 */
class AnomalyDashboardController extends Controller
{
    public function index(): View
    {
        $unresolvedCount = SensorAnomaly::where('resolved', false)->count();

        $offlineDevicesCount = SensorAnomaly::where('anomaly_type', 'device_offline')
            ->where('resolved', false)
            ->distinct('device_id')
            ->count('device_id');

        $flaggedTodayCount = SensorAnomaly::whereDate('detected_at', today())->count();

        $totalTrackedCount = SensorAnomaly::count();

        $recentAnomalies = SensorAnomaly::with(['device', 'hive'])
            ->orderByDesc('detected_at')
            ->limit(20)
            ->get();

        $byType = SensorAnomaly::where('resolved', false)
            ->selectRaw('anomaly_type, count(*) as total')
            ->groupBy('anomaly_type')
            ->pluck('total', 'anomaly_type');

        return view('admin.anomaly.dashboard', compact(
            'unresolvedCount',
            'offlineDevicesCount',
            'flaggedTodayCount',
            'totalTrackedCount',
            'recentAnomalies',
            'byType',
        ));
    }
}

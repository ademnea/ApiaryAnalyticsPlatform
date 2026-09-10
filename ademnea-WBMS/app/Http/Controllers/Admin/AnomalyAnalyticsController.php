<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensorAnomaly;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * SRS UC-IOT-11: per-hive anomaly heatmap / trend analytics over a
 * configurable window.
 */
class AnomalyAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('days', 7);
        $days = in_array($days, [7, 30], true) ? $days : 7;

        $anomalies = SensorAnomaly::with(['hive', 'device'])
            ->where('detected_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->orderByDesc('detected_at')
            ->get();

        // Every date in the window, oldest first, so the heatmap always
        // shows a full grid even for dates with zero anomalies.
        $dates = collect(range(0, $days - 1))
            ->map(fn ($i) => today()->subDays($days - 1 - $i)->toDateString());

        $byHiveAndDate = $anomalies
            ->whereNotNull('hive_id')
            ->groupBy('hive_id')
            ->map(fn ($group) => $group->groupBy(fn ($a) => $a->detected_at->toDateString())->map->count());

        $hives = $anomalies->pluck('hive')->filter()->unique('id')->sortBy('display_name');

        $maxDailyCount = max(1, $byHiveAndDate->flatten()->max() ?? 1);

        return view('admin.anomaly.analytics', compact(
            'days',
            'anomalies',
            'dates',
            'byHiveAndDate',
            'hives',
            'maxDailyCount',
        ));
    }
}

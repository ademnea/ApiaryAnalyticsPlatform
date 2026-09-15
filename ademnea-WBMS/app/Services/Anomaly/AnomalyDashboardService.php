<?php

namespace App\Services\Anomaly;

use App\Models\Hive;
use App\Models\SensorAnomaly;

/**
 * SRS UC-IOT-09 (anomaly half) and UC-IOT-11: hive-condition overview and
 * the per-hive heatmap. Device health lives in DeviceFleetService; only its
 * open-issue count is surfaced here.
 */
class AnomalyDashboardService
{
    public const HEATMAP_WINDOWS = [7, 30];

    public function hiveConditionsSummary(): array
    {
        $hiveConditions = fn () => SensorAnomaly::query()->hiveConditions();

        $mostAffected = $hiveConditions()->open()
            ->whereNotNull('hive_id')
            ->selectRaw('hive_id, count(*) as total')
            ->groupBy('hive_id')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'hive_id');

        return [
            'openCount' => $hiveConditions()->withStatus('open')->count(),
            'acknowledgedCount' => $hiveConditions()->withStatus('acknowledged')->count(),
            'hivesAffectedCount' => $hiveConditions()->open()->whereNotNull('hive_id')->distinct()->count('hive_id'),
            'newTodayCount' => $hiveConditions()->whereDate('detected_at', today())->count(),
            'openDeviceIssuesCount' => SensorAnomaly::query()->deviceIssues()->open()->count(),
            'latestOpen' => $hiveConditions()->open()
                ->with(['device', 'hive'])
                ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
                ->limit(10)
                ->get(),
            'bySensorType' => $hiveConditions()->open()
                ->selectRaw('sensor_type, count(*) as total')
                ->groupBy('sensor_type')
                ->pluck('total', 'sensor_type'),
            'byType' => $hiveConditions()->open()
                ->selectRaw('anomaly_type, count(*) as total')
                ->groupBy('anomaly_type')
                ->pluck('total', 'anomaly_type'),
            'mostAffected' => $mostAffected,
            'mostAffectedHives' => Hive::with('apiary')
                ->whereIn('id', $mostAffected->keys())
                ->get()
                ->sortByDesc(fn (Hive $hive) => $mostAffected[$hive->id])
                ->values(),
        ];
    }

    /** Per-hive anomaly counts per day over the last $days (7 or 30). */
    public function heatmap(int $days): array
    {
        $days = in_array($days, self::HEATMAP_WINDOWS, true) ? $days : self::HEATMAP_WINDOWS[0];

        $anomalies = SensorAnomaly::hiveConditions()
            ->with(['hive', 'device'])
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

        return [
            'days' => $days,
            'anomalies' => $anomalies,
            'dates' => $dates,
            'byHiveAndDate' => $byHiveAndDate,
            'hives' => $anomalies->pluck('hive')->filter()->unique('id')->sortBy('display_name'),
            'maxDailyCount' => max(1, $byHiveAndDate->flatten()->max() ?? 1),
        ];
    }
}

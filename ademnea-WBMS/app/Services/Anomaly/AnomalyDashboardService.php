<?php

namespace App\Services\Anomaly;

use App\Enums\SensorMetric;
use App\Models\AlertThreshold;
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

    public function __construct(private readonly AnomalyEvidenceService $evidence)
    {
    }

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

        $latestOpen = $hiveConditions()->open()
            ->with(['device', 'hive'])
            ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
            ->limit(10)
            ->get();

        return [
            'openCount' => $hiveConditions()->withStatus('open')->count(),
            'acknowledgedCount' => $hiveConditions()->withStatus('acknowledged')->count(),
            'hivesAffectedCount' => $hiveConditions()->open()->whereNotNull('hive_id')->distinct()->count('hive_id'),
            'newTodayCount' => $hiveConditions()->whereDate('detected_at', today())->count(),
            'openDeviceIssuesCount' => SensorAnomaly::query()->deviceIssues()->open()->count(),
            'latestOpen' => $latestOpen,
            'headlines' => $latestOpen->mapWithKeys(fn (SensorAnomaly $anomaly) => [$anomaly->id => $this->evidence->headline($anomaly)]),
            'ruleGroups' => $this->ruleGroups(),
            'mostAffected' => $mostAffected,
            'mostAffectedHives' => Hive::with('apiary')
                ->whereIn('id', $mostAffected->keys())
                ->get()
                ->sortByDesc(fn (Hive $hive) => $mostAffected[$hive->id])
                ->values(),
        ];
    }

    /**
     * The hive rules that run on every reading, grouped by what a violation
     * means: the sensor is wrong, or the colony is behaving unusually. Each
     * carries its fleet-wide limits (a hive can override them), who it
     * notifies, and how many incidents are open.
     *
     * @return array<int, array{title: string, summary: string, icon: string, rules: array<int, array<string, mixed>>}>
     */
    private function ruleGroups(): array
    {
        $open = SensorAnomaly::hiveConditions()->open()
            ->selectRaw('anomaly_type, count(*) as total, count(distinct hive_id) as hives')
            ->groupBy('anomaly_type')
            ->get()
            ->keyBy('anomaly_type');

        $number = fn (mixed $value): string => (string) round((float) $value, 2);

        $rule = fn (string $type, array $limits): array => [
            'type' => $type,
            'label' => SensorAnomaly::labelFor($type),
            'icon' => (new SensorAnomaly(['anomaly_type' => $type]))->icon(),
            'description' => DetectionLimitService::RULE_DESCRIPTIONS[$type],
            'limits' => $limits,
            'notifies' => AlertRouting::describe($type),
            'openCount' => (int) ($open[$type]->total ?? 0),
            'hivesAffected' => (int) ($open[$type]->hives ?? 0),
        ];

        $plausibleRanges = array_map(function (SensorMetric $metric) use ($number) {
            [$min, $max] = $metric->bounds();

            return "{$metric->label()}: {$number($min)} to {$number($max)} {$metric->unit()}";
        }, SensorMetric::cases());

        return [
            [
                'title' => 'Sensor faults',
                'summary' => DetectionLimitService::GROUP_SUMMARIES['Sensor faults'],
                'icon' => 'bi-tools',
                'rules' => [
                    $rule('static_threshold_breach', $plausibleRanges),
                    $rule('frozen_sensor', [
                        $number(AlertThreshold::get('stuck_sensor_reading_count', 10)).' identical readings in a row',
                    ]),
                ],
            ],
            [
                'title' => 'Colony signals',
                'summary' => DetectionLimitService::GROUP_SUMMARIES['Colony signals'],
                'icon' => 'bi-hexagon',
                'rules' => [
                    $rule('statistical_deviation', [
                        'More than '.$number(AlertThreshold::get('zscore_stddev_threshold', 3)).' standard deviations from the average',
                        'Needs at least 10 readings in the 24 hours',
                    ]),
                ],
            ],
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

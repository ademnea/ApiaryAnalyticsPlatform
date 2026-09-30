<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\RollingStatsService;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;

/**
 * Flags a reading more than N standard deviations from the hive's rolling
 * 24-hour mean for that (sensor_type, channel). Needs at least
 * MIN_SAMPLE_COUNT readings in the window, so it never judges against a
 * near-empty baseline.
 */
class ZScoreRuleEvaluator
{
    private const MIN_SAMPLE_COUNT = 10;

    public function __construct(private readonly RollingStatsService $rollingStats)
    {
    }

    public function evaluate(Model $reading, string $sensorType): ?SensorAnomaly
    {
        $threshold = (float) AlertThreshold::getForHive((int) $reading->hive_id, 'zscore_stddev_threshold', 3);

        foreach (SensorChannels::channelsFor($sensorType) as $channel) {
            $column = SensorChannels::columnFor($sensorType, $channel);
            $value = $reading->{$column};

            if ($value === null) {
                continue;
            }

            $baseline = $this->rollingStats->baselineBefore($reading, $sensorType, $channel);

            if (! $baseline || $baseline['count'] < self::MIN_SAMPLE_COUNT) {
                continue;
            }

            $stdDev = $baseline['stddev'];

            if ($stdDev > 0 && abs($value - $baseline['mean']) > $threshold * $stdDev) {
                return SensorAnomaly::recordOrTouch([
                    'device_id' => $reading->device_id,
                    'hive_id' => $reading->hive_id,
                    'sensor_type' => $sensorType,
                    'anomaly_type' => 'statistical_deviation',
                    'anomaly_score' => 1.0,
                    'record_value' => [$column => $value, 'mean' => round($baseline['mean'], 3), 'stddev' => round($stdDev, 3)],
                    'detection_layer' => 'rules',
                    'detected_at' => $reading->recorded_at,
                ]);
            }
        }

        return null;
    }
}

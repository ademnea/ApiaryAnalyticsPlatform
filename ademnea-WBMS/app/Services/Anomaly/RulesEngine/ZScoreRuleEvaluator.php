<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\RollingStatsService;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;

/**
 * Flags a reading more than N standard deviations from the hive's rolling
 * mean for that (sensor_type, channel). Requires a sample_count warm-up
 * floor before evaluating, to avoid flagging against a near-empty window.
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

            $stats = $this->rollingStats->currentStats((int) $reading->hive_id, $sensorType, $channel);

            if (! $stats || $stats->sample_count < self::MIN_SAMPLE_COUNT) {
                continue;
            }

            $variance = $stats->variance / $stats->sample_count;
            $stdDev = sqrt($variance);

            if ($stdDev > 0 && abs($value - $stats->mean) > $threshold * $stdDev) {
                return SensorAnomaly::create([
                    'device_id' => $reading->device_id,
                    'hive_id' => $reading->hive_id,
                    'sensor_type' => $sensorType,
                    'anomaly_type' => 'statistical_deviation',
                    'anomaly_score' => 1.0,
                    'record_value' => [$column => $value, 'mean' => $stats->mean, 'stddev' => $stdDev],
                    'detection_layer' => 'rules',
                    'detected_at' => $reading->recorded_at,
                ]);
            }
        }

        return null;
    }
}

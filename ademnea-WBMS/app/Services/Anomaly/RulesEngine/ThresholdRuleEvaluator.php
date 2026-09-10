<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;

/**
 * Static physically-plausible-range check. Humidity's 0-100% bound and
 * co2's 0ppm floor are physical constants, not admin-overridable; the rest
 * are seeded AlertThreshold rows (per-hive overridable for free).
 */
class ThresholdRuleEvaluator
{
    public function evaluate(Model $reading, string $sensorType): ?SensorAnomaly
    {
        foreach (SensorChannels::channelsFor($sensorType) as $channel) {
            $column = SensorChannels::columnFor($sensorType, $channel);
            $value = $reading->{$column};

            if ($value === null) {
                continue;
            }

            [$min, $max] = $this->bounds((int) $reading->hive_id, $sensorType);

            if ($value < $min || $value > $max) {
                return SensorAnomaly::create([
                    'device_id' => $reading->device_id,
                    'hive_id' => $reading->hive_id,
                    'sensor_type' => $sensorType,
                    'anomaly_type' => 'static_threshold_breach',
                    'anomaly_score' => 1.0,
                    'record_value' => [$column => $value],
                    'detection_layer' => 'rules',
                    'detected_at' => $reading->recorded_at,
                ]);
            }
        }

        return null;
    }

    /** @return array{0: float, 1: float} */
    private function bounds(int $hiveId, string $sensorType): array
    {
        return match ($sensorType) {
            'temperature' => [
                (float) AlertThreshold::getForHive($hiveId, 'temp_min_c', -10),
                (float) AlertThreshold::getForHive($hiveId, 'temp_max_c', 60),
            ],
            'humidity' => [0.0, 100.0],
            'co2' => [0.0, (float) AlertThreshold::getForHive($hiveId, 'co2_max_ppm', 5000)],
            'weight' => [
                (float) AlertThreshold::getForHive($hiveId, 'weight_min_kg', 5),
                (float) AlertThreshold::getForHive($hiveId, 'weight_max_kg', 120),
            ],
        };
    }
}

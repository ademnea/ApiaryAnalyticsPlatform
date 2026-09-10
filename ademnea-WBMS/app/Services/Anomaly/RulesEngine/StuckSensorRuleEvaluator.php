<?php

namespace App\Services\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;

/**
 * Flags a frozen/stuck sensor: the last N consecutive readings for a
 * (device, sensor_type, channel) are all identical.
 */
class StuckSensorRuleEvaluator
{
    public function evaluate(Model $reading, string $sensorType): ?SensorAnomaly
    {
        $count = (int) AlertThreshold::getForHive((int) $reading->hive_id, 'stuck_sensor_reading_count', 10);
        $class = SensorChannels::modelClassFor($sensorType);

        $recent = $class::where('device_id', $reading->device_id)
            ->orderByDesc('recorded_at')
            ->limit($count)
            ->get();

        if ($recent->count() < $count) {
            return null;
        }

        foreach (SensorChannels::channelsFor($sensorType) as $channel) {
            $column = SensorChannels::columnFor($sensorType, $channel);
            $values = $recent->pluck($column)->filter(fn ($value) => $value !== null);

            if ($values->count() < $count) {
                continue;
            }

            if ($values->unique()->count() === 1) {
                return SensorAnomaly::create([
                    'device_id' => $reading->device_id,
                    'hive_id' => $reading->hive_id,
                    'sensor_type' => $sensorType,
                    'anomaly_type' => 'frozen_sensor',
                    'anomaly_score' => 1.0,
                    'record_value' => [$column => $values->first()],
                    'detection_layer' => 'rules',
                    'detected_at' => $reading->recorded_at,
                ]);
            }
        }

        return null;
    }
}

<?php

namespace App\Services\Anomaly\RulesEngine\Support;

use App\Models\HiveCarbondioxide;
use App\Models\HiveHumidity;
use App\Models\HiveTemperature;
use App\Models\HiveWeight;
use InvalidArgumentException;

/**
 * Shared sensor-type shape knowledge for the rules engine: which sensor
 * types are 3-zone (temperature, humidity) vs single-value (co2, weight),
 * and which typed model/column backs each. Centralized here because
 * ThresholdRuleEvaluator, StuckSensorRuleEvaluator, ZScoreRuleEvaluator, and
 * RollingStatsService all need the exact same mapping.
 */
class SensorChannels
{
    private const ZONE_SENSORS = ['temperature', 'humidity'];
    private const ZONES = ['honey_section', 'brood_section', 'exterior'];

    private const MODEL_MAP = [
        'temperature' => HiveTemperature::class,
        'humidity' => HiveHumidity::class,
        'co2' => HiveCarbondioxide::class,
        'weight' => HiveWeight::class,
    ];

    /** @return array<int, string|null> the channels to evaluate/track for a sensor type */
    public static function channelsFor(string $sensorType): array
    {
        return in_array($sensorType, self::ZONE_SENSORS, true) ? self::ZONES : [null];
    }

    /** The reading column for a given (sensor type, channel) pair. */
    public static function columnFor(string $sensorType, ?string $channel): string
    {
        if ($channel !== null) {
            return $channel;
        }

        return match ($sensorType) {
            'co2' => 'co2_level',
            'weight' => 'weight_kg',
            default => throw new InvalidArgumentException("Unknown single-value sensor type: {$sensorType}"),
        };
    }

    public static function modelClassFor(string $sensorType): string
    {
        return self::MODEL_MAP[$sensorType]
            ?? throw new InvalidArgumentException("Unknown sensor type: {$sensorType}");
    }
}

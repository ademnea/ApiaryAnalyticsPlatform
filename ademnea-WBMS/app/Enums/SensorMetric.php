<?php

namespace App\Enums;

use App\Models\AlertThreshold;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use App\Services\Anomaly\RulesEngine\ThresholdRuleEvaluator;
use Illuminate\Database\Eloquent\Model;

/**
 * The four numeric sensor streams on the Sensor Monitoring pages.
 *
 * Case values match the `sensor_type` vocabulary used by ingestion, the
 * rules engine and `sensor_anomalies`. Which table and columns back each
 * stream is asked of SensorChannels, and the plausible range of
 * ThresholdRuleEvaluator, so neither fact is repeated here.
 */
enum SensorMetric: string
{
    case Temperature = 'temperature';
    case Humidity = 'humidity';
    case Co2 = 'co2';
    case Weight = 'weight';

    private const COLUMN_LABELS = [
        'honey_section' => 'Honey Super',
        'brood_section' => 'Brood Chamber',
        'exterior' => 'Exterior',
        'co2_level' => 'CO₂ Level',
        'weight_kg' => 'Gross Weight',
    ];

    /** Chart colours from the AdEMNEA palette in layouts/app.blade.php. */
    private const COLUMN_COLORS = [
        'honey_section' => '#D4A017',
        'brood_section' => '#1B4332',
        'exterior' => '#0057b8',
        'co2_level' => '#2D6A4F',
        'weight_kg' => '#2D6A4F',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Temperature => 'Temperature',
            self::Humidity => 'Humidity',
            self::Co2 => 'CO₂ Levels',
            self::Weight => 'Hive Weight',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Temperature => '°C',
            self::Humidity => '%',
            self::Co2 => 'ppm',
            self::Weight => 'kg',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Temperature => 'bi-thermometer-half',
            self::Humidity => 'bi-droplet-half',
            self::Co2 => 'bi-wind',
            self::Weight => 'bi-speedometer',
        };
    }

    /** Decimal places shown: whole ppm for CO₂, grams for weight. */
    public function precision(): int
    {
        return match ($this) {
            self::Co2 => 0,
            self::Weight => 2,
            default => 1,
        };
    }

    public function routeName(): string
    {
        return 'admin.monitoring.'.$this->value;
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return SensorChannels::modelClassFor($this->value);
    }

    /** @return array<string, string> reading column => label, in display order */
    public function columns(): array
    {
        $columns = [];

        foreach (SensorChannels::channelsFor($this->value) as $channel) {
            $column = SensorChannels::columnFor($this->value, $channel);
            $columns[$column] = self::COLUMN_LABELS[$column];
        }

        return $columns;
    }

    public function isMultiZone(): bool
    {
        return count($this->columns()) > 1;
    }

    /**
     * The column that stands for the whole stream when one number must do.
     * For zoned streams that is the brood chamber: the colony regulates it,
     * whereas the exterior zone mostly tracks the weather.
     */
    public function primaryColumn(): string
    {
        return $this->isMultiZone() ? 'brood_section' : array_key_first($this->columns());
    }

    public function colorFor(string $column): string
    {
        return self::COLUMN_COLORS[$column] ?? '#40916C';
    }

    /** One distinct colour per stream, for charts that show all four together. */
    public function streamColor(): string
    {
        return match ($this) {
            self::Temperature => '#D4A017',
            self::Humidity => '#0057b8',
            self::Co2 => '#40916C',
            self::Weight => '#1B4332',
        };
    }

    /**
     * The range a healthy colony holds its brood chamber in, shaded on charts.
     * Unlike bounds(), leaving it is not a sensor fault but a colony signal:
     * a queenless, weak or absconded colony stops regulating the brood nest.
     * Only temperature and humidity have an established target.
     *
     * @return array{min: float, max: float}|null
     */
    public function idealBand(?int $hiveId = null): ?array
    {
        [$minKey, $maxKey, $min, $max] = match ($this) {
            self::Temperature => ['brood_temp_ideal_min_c', 'brood_temp_ideal_max_c', 32.0, 36.0],
            self::Humidity => ['brood_humidity_ideal_min_pct', 'brood_humidity_ideal_max_pct', 50.0, 70.0],
            default => [null, null, null, null],
        };

        if ($minKey === null) {
            return null;
        }

        $threshold = fn (string $key, float $default): float => (float) ($hiveId === null
            ? AlertThreshold::get($key, $default)
            : AlertThreshold::getForHive($hiveId, $key, $default));

        return ['min' => $threshold($minKey, $min), 'max' => $threshold($maxKey, $max)];
    }

    /**
     * The plausible range drawn on the chart, identical to the one the
     * rules engine enforces on ingestion.
     *
     * @return array{0: float, 1: float}
     */
    public function bounds(?int $hiveId = null): array
    {
        return ThresholdRuleEvaluator::bounds($hiveId, $this->value);
    }
}

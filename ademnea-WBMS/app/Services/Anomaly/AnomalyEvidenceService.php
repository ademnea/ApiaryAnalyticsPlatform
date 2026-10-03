<?php

namespace App\Services\Anomaly;

use App\Enums\SensorMetric;
use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\SensorAnomaly;
use Carbon\CarbonInterval;

/**
 * What the incident page shows about one anomaly: a plain-language account
 * of what the rule saw, the readings around it, and who was notified.
 */
class AnomalyEvidenceService
{
    /** Readings charted before the incident opened, and after it was last seen. */
    private const CHART_LEAD_HOURS = 24;
    private const CHART_TRAIL_HOURS = 6;

    /** A long-running incident still gets a readable chart: its first days only. */
    private const CHART_MAX_HOURS = 72;
    private const CHART_MAX_POINTS = 1000;

    /**
     * One or two sentences on what was detected. A hive condition is
     * described by the reading that opened it, the one marked on the chart;
     * a device issue by its latest state, since that is what is still wrong.
     */
    public function explain(SensorAnomaly $anomaly): string
    {
        $metric = SensorMetric::tryFrom($anomaly->sensor_type);
        $values = ($metric ? null : $anomaly->last_record_value) ?: $anomaly->record_value ?: [];

        $sentence = $metric
            ? $this->explainHiveCondition($anomaly, $metric, $values)
            : $this->explainDeviceIssue($anomaly, $values);

        return $sentence ?? 'Triggered by '.SensorAnomaly::formatValues($values).'.';
    }

    /** The first sentence of explain(): what was seen, without the reasoning. For list rows. */
    public function headline(SensorAnomaly $anomaly): string
    {
        // Sentences end at ". " followed by a capital; "71.2 °C" must not split.
        return preg_split('/(?<=\.)\s+(?=[A-Z])/u', $this->explain($anomaly), 2)[0];
    }

    /**
     * The flagged channel's readings around the incident, with the limit the
     * rule judged against. Null for device issues and when no readings remain.
     *
     * @return array{labels: array<int, string>, values: array<int, float>, flagged: array<int, bool>, series: string, unit: string, precision: int, color: string, band: ?array{label: string, min: float, max: float}}|null
     */
    public function chart(SensorAnomaly $anomaly): ?array
    {
        $metric = SensorMetric::tryFrom($anomaly->sensor_type);
        $column = $metric ? $this->flaggedColumn($metric, $anomaly->record_value ?? []) : null;

        if ($column === null || $anomaly->hive_id === null) {
            return null;
        }

        $from = $anomaly->detected_at->copy()->subHours(self::CHART_LEAD_HOURS);
        $to = ($anomaly->last_seen_at ?? $anomaly->detected_at)->copy()->addHours(self::CHART_TRAIL_HOURS)
            ->min($from->copy()->addHours(self::CHART_MAX_HOURS));

        $readings = $metric->modelClass()::where('hive_id', $anomaly->hive_id)
            ->whereBetween('recorded_at', [$from, $to])
            ->whereNotNull($column)
            ->orderBy('recorded_at')
            ->limit(self::CHART_MAX_POINTS)
            ->get(['recorded_at', $column, 'suspect']);

        if ($readings->isEmpty()) {
            return null;
        }

        return [
            'labels' => $readings->map(fn ($reading) => $reading->recorded_at->format('d M H:i'))->all(),
            'values' => $readings->map(fn ($reading) => (float) $reading->{$column})->all(),
            // Suspect readings, plus the one that opened the incident: a
            // statistical deviation is flagged without being marked suspect.
            'flagged' => $readings->map(fn ($reading) => (bool) $reading->suspect || $reading->recorded_at->equalTo($anomaly->detected_at))->all(),
            'series' => $this->subject($metric, $column),
            'unit' => $metric->unit(),
            'precision' => $metric->precision(),
            'color' => $metric->colorFor($column),
            'band' => $this->band($anomaly, $metric),
        ];
    }

    /**
     * Who this anomaly type is routed to, and why nothing was delivered when
     * the incident has neither delivery logs nor a farmer alert. Expects
     * `notifications` and `alerts` to be loaded.
     *
     * @return array{routes: array<int, array{recipient: string, channels: array<int, string>}>, emptyReason: ?string}
     */
    public function notificationSummary(SensorAnomaly $anomaly): array
    {
        $routes = AlertRouting::describe($anomaly->anomaly_type);

        return [
            'routes' => $routes,
            'emptyReason' => $this->emptyReason($anomaly, $routes),
        ];
    }

    private function emptyReason(SensorAnomaly $anomaly, array $routes): ?string
    {
        if ($anomaly->notifications->isNotEmpty() || $anomaly->alerts->isNotEmpty()) {
            return null;
        }

        if ($routes === []) {
            return 'This type is shown on the dashboard only, so nobody is notified.';
        }

        if (! $anomaly->alerted) {
            $minutes = (new Alert())->cooldownMinutesFor($anomaly->anomaly_type);

            return $minutes !== null
                ? "No notification was sent. Most likely the cooldown suppressed it: this device had already raised the same condition within the previous {$minutes} minutes."
                : 'No notification was sent for this incident.';
        }

        return 'The incident was marked as notified, but no delivery was recorded. Either no recipient has a contact for the routed channels, or it predates delivery logging.';
    }

    private function explainHiveCondition(SensorAnomaly $anomaly, SensorMetric $metric, array $values): ?string
    {
        $column = $this->flaggedColumn($metric, $values);

        if ($column === null) {
            return null;
        }

        $subject = $this->subject($metric, $column);
        $value = $this->quantity($metric, $values[$column]);
        $hiveId = $anomaly->hive_id;

        switch ($anomaly->anomaly_type) {
            case 'static_threshold_breach':
                [$min, $max] = $metric->bounds($hiveId);

                return "{$subject} read {$value}. The plausible range is {$this->quantity($metric, $min)} to {$this->quantity($metric, $max)}, "
                    .'so the value cannot be real and the reading was marked suspect.';

            case 'frozen_sensor':
                $count = (int) $this->threshold($hiveId, 'stuck_sensor_reading_count', 10);

                return "{$subject} reported exactly {$value} on {$count} readings in a row. "
                    .'A working sensor always drifts a little, so the reading was marked suspect.';

            case 'statistical_deviation':
                if (! isset($values['mean'], $values['stddev'])) {
                    return null;
                }

                $limit = (float) $this->threshold($hiveId, 'zscore_stddev_threshold', 3);
                $distance = $values['stddev'] > 0 ? round(abs($values[$column] - $values['mean']) / $values['stddev'], 1) : null;

                return "{$subject} read {$value}. Over the 24 hours before, this hive averaged {$this->quantity($metric, $values['mean'])} "
                    ."with a standard deviation of {$this->quantity($metric, $values['stddev'])}"
                    .($distance !== null ? ", which puts this reading {$distance} standard deviations away" : '')
                    .". The limit is {$this->number($limit)}.";

            default:
                return null;
        }
    }

    private function explainDeviceIssue(SensorAnomaly $anomaly, array $values): ?string
    {
        $hiveId = $anomaly->hive_id;
        $has = fn (string ...$keys): bool => collect($keys)->every(fn (string $key) => isset($values[$key]));

        return match (true) {
            $anomaly->anomaly_type === 'critical_battery' && $has('battery_level') => "Battery was at {$this->number($values['battery_level'])} %, at or below the critical limit of {$this->number($this->threshold($hiveId, 'critical_battery_pct', 5))} %.",
            $anomaly->anomaly_type === 'low_battery' && $has('battery_level') => "Battery was at {$this->number($values['battery_level'])} %, at or below the low-battery limit of {$this->number($this->threshold($hiveId, 'low_battery_pct', 20))} %.",
            $anomaly->anomaly_type === 'weak_signal' && $has('signal_strength') => "Signal strength was {$this->number($values['signal_strength'])} dBm, at or below the weak-signal limit of {$this->number($this->threshold($hiveId, 'weak_signal_rssi_dbm', -85))} dBm"
                .($has('weak_for_minutes') ? ', and had been weak for '.$this->duration($values['weak_for_minutes']) : '').'.',
            $anomaly->anomaly_type === 'storage_full' && $has('storage_usage') => "Storage was {$this->number($values['storage_usage'])} % full. The limit is {$this->number($this->threshold($hiveId, 'storage_full_pct', 90))} %.",
            $anomaly->anomaly_type === 'reboot_loop' && $has('reboot_count_delta') => "The device rebooted {$this->number($values['reboot_count_delta'])} times within an hour. The limit is {$this->number($this->threshold($hiveId, 'reboot_loop_count_per_hour', 3))}.",
            $anomaly->anomaly_type === 'firmware_outdated' && $has('firmware_version', 'latest_firmware_version') => "The device was running firmware {$values['firmware_version']}. The current release is {$values['latest_firmware_version']}.",
            $anomaly->anomaly_type === 'device_offline' && $has('data_gap_minutes') => 'No heartbeat or data had arrived for '.$this->duration($values['data_gap_minutes'])
                .($has('expected_interval_minutes') ? '. The device is expected to report every '.$this->duration($values['expected_interval_minutes']) : '').'.',
            $anomaly->anomaly_type === 'submission_delay' && $has('median_interval_minutes', 'expected_interval_minutes') => 'The device was reporting about every '.$this->duration($values['median_interval_minutes'])
                .'. It is expected every '.$this->duration($values['expected_interval_minutes']).'.',
            $anomaly->anomaly_type === 'high_anomaly_rate' && $has('flagged_readings', 'readings', 'rate_pct') => "{$this->number($values['flagged_readings'])} of the device's {$this->number($values['readings'])} readings in the last hour ({$this->number($values['rate_pct'])} %) were marked suspect.",
            default => null,
        };
    }

    /** The limit the rule judged the reading against, as a band for the chart. */
    private function band(SensorAnomaly $anomaly, SensorMetric $metric): ?array
    {
        if ($anomaly->anomaly_type === 'static_threshold_breach') {
            [$min, $max] = $metric->bounds($anomaly->hive_id);

            return ['label' => 'Plausible range', 'min' => $min, 'max' => $max];
        }

        // The baseline as it stood when the incident opened.
        $baseline = $anomaly->record_value ?? [];

        if ($anomaly->anomaly_type === 'statistical_deviation' && isset($baseline['mean'], $baseline['stddev'])) {
            $limit = (float) $this->threshold($anomaly->hive_id, 'zscore_stddev_threshold', 3);

            return [
                'label' => "Expected range (mean ± {$this->number($limit)} standard deviations)",
                'min' => $baseline['mean'] - $limit * $baseline['stddev'],
                'max' => $baseline['mean'] + $limit * $baseline['stddev'],
            ];
        }

        return null;
    }

    /** The reading column the rule flagged: the first recorded key that is one of the stream's columns. */
    private function flaggedColumn(SensorMetric $metric, array $values): ?string
    {
        return collect(array_keys($metric->columns()))->first(fn (string $column) => isset($values[$column]));
    }

    /** "Brood Chamber temperature" for a zoned stream, "Gross Weight" for a single-value one. */
    private function subject(SensorMetric $metric, string $column): string
    {
        $label = $metric->columns()[$column];

        return $metric->isMultiZone() ? $label.' '.mb_strtolower($metric->label()) : $label;
    }

    private function quantity(SensorMetric $metric, float|int|string $value): string
    {
        return number_format((float) $value, $metric->precision()).' '.$metric->unit();
    }

    /** A number without trailing zeros: 20, 4.5, -85. */
    private function number(mixed $value): string
    {
        return is_numeric($value) ? (string) round((float) $value, 2) : (string) $value;
    }

    private function duration(mixed $minutes): string
    {
        return CarbonInterval::minutes(max(1, (int) round((float) $minutes)))->cascade()->forHumans(['parts' => 2]);
    }

    private function threshold(?int $hiveId, string $key, mixed $default): mixed
    {
        return $hiveId !== null
            ? AlertThreshold::getForHive($hiveId, $key, $default)
            : AlertThreshold::get($key, $default);
    }
}

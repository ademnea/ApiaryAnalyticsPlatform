<?php

namespace App\Services\Anomaly;

use App\Models\AlertThreshold;
use App\Models\Hive;
use App\Models\SensorAnomaly;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The limits the condition-monitoring rules judge against, as a settings
 * page: which limits exist, their allowed ranges, and their fleet-wide and
 * per-hive values. They are stored as alert_thresholds rows, which is where
 * the rules read them from.
 */
class DetectionLimitService
{
    public const RULE_DESCRIPTIONS = [
        'static_threshold_breach' => 'A value outside what the sensor could physically measure.',
        'frozen_sensor' => 'The same value repeated, where a working sensor would drift a little.',
        'statistical_deviation' => "A value far from this hive's own average over the previous 24 hours.",
    ];

    public const GROUP_SUMMARIES = [
        'Sensor faults' => 'The hardware is sending values that cannot be trusted. The reading is marked suspect and left out of averages.',
        'Colony signals' => 'The sensor looks fine, but the hive is behaving unusually. It may be a real colony event.',
        'Device health' => 'The unit itself needs attention: power, signal, storage or connectivity.',
    ];

    /** Pairs where the first limit must stay below the second. */
    public const ORDERED_PAIRS = [
        ['temp_min_c', 'temp_max_c'],
        ['weight_min_kg', 'weight_max_kg'],
        ['critical_battery_pct', 'low_battery_pct'],
    ];

    /**
     * Every group and rule. A rule's `rows` are what the form lays out: each
     * a label and its `items`, input label => key into the rule's `fields`.
     * Each field carries its fleet value and the value to show: the fleet
     * value, or the hive's override (null when the hive inherits it).
     *
     * @return array<int, array{id: string, title: string, summary: string, icon: string, rules: array<int, array<string, mixed>>}>
     */
    public function groups(?Hive $hive = null): array
    {
        $keys = array_keys($this->fields());
        $fleet = AlertThreshold::whereNull('hive_id')->whereIn('key', $keys)->pluck('value', 'key');
        $overrides = $hive
            ? AlertThreshold::where('hive_id', $hive->id)->whereIn('key', $keys)->pluck('value', 'key')
            : collect();

        return array_map(function (array $group) use ($hive, $fleet, $overrides) {
            $group['rules'] = array_map(function (array $rule) use ($hive, $fleet, $overrides) {
                $rule['fields'] = array_map(function (array $field) use ($hive, $fleet, $overrides) {
                    $fleetValue = $this->display($fleet[$field['key']] ?? $field['default']);

                    return $field + [
                        'fleetValue' => $fleetValue,
                        'value' => $hive ? (isset($overrides[$field['key']]) ? $this->display($overrides[$field['key']]) : null) : $fleetValue,
                    ];
                }, array_column($rule['fields'], null, 'key'));

                return $rule;
            }, $group['rules']);

            return $group;
        }, $this->catalogue());
    }

    /** @return array<string, array<string, mixed>> every field, keyed by its alert_thresholds key */
    public function fields(): array
    {
        return collect($this->catalogue())
            ->flatMap(fn (array $group) => $group['rules'])
            ->flatMap(fn (array $rule) => $rule['fields'])
            ->keyBy('key')
            ->all();
    }

    /**
     * The values in force once $submitted is saved: the submitted value, or
     * for a hive that leaves a field empty, the fleet value it inherits.
     *
     * @return array<string, string>
     */
    public function effectiveValues(?Hive $hive, array $submitted): array
    {
        $fleet = AlertThreshold::whereNull('hive_id')->whereIn('key', array_keys($this->fields()))->pluck('value', 'key');

        return collect($this->fields())
            ->map(function (array $field, string $key) use ($hive, $submitted, $fleet) {
                $value = $hive && $field['fleetOnly'] ? null : ($submitted[$key] ?? null);

                return (string) ($value !== null && $value !== '' ? $value : ($fleet[$key] ?? $field['default']));
            })
            ->all();
    }

    /**
     * Saves fleet values, or one hive's overrides. For a hive, an empty
     * field removes the override so the hive inherits the fleet value again.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(?Hive $hive, array $values): void
    {
        DB::transaction(function () use ($hive, $values) {
            foreach ($this->fields() as $key => $field) {
                if ($hive && $field['fleetOnly']) {
                    continue;
                }

                $value = $values[$key] ?? null;

                if ($value === null || $value === '') {
                    if ($hive) {
                        // Deleted one by one so the model clears its cached value.
                        AlertThreshold::where('hive_id', $hive->id)->where('key', $key)->get()->each->delete();
                    }

                    continue;
                }

                $threshold = AlertThreshold::firstOrNew(['key' => $key, 'hive_id' => $hive?->id]);
                $threshold->description ??= $field['label'].($field['unit'] ? " ({$field['unit']})" : '').'.';
                $threshold->value = $this->display($value);
                $threshold->save();
            }
        });
    }

    /** Rule cards that hold the limits for more than one anomaly type. */
    private const RULE_ALIASES = [
        'critical_battery' => 'low_battery',
        'submission_delay' => 'device_offline',
    ];

    /** The rule card on the limits page that an anomaly type's limits live on. */
    public function ruleFor(string $anomalyType): string
    {
        return self::RULE_ALIASES[$anomalyType] ?? $anomalyType;
    }

    /** Hives that override at least one limit, with how many. */
    public function hivesWithOverrides(): Collection
    {
        $counts = AlertThreshold::whereNotNull('hive_id')
            ->whereIn('key', array_keys($this->fields()))
            ->selectRaw('hive_id, count(*) as total')
            ->groupBy('hive_id')
            ->pluck('total', 'hive_id');

        return Hive::whereIn('id', $counts->keys())
            ->orderBy('display_name')
            ->get()
            ->each(fn (Hive $hive) => $hive->setAttribute('override_count', $counts[$hive->id]));
    }

    /** A stored value as the form shows it: numbers without trailing zeros. */
    private function display(mixed $value): string
    {
        return is_numeric($value) ? (string) (float) $value : (string) $value;
    }

    private function catalogue(): array
    {
        return [
            [
                'id' => 'sensor-faults',
                'title' => 'Sensor faults',
                'summary' => self::GROUP_SUMMARIES['Sensor faults'],
                'icon' => 'bi-tools',
                'rules' => [
                    $this->rule('static_threshold_breach', self::RULE_DESCRIPTIONS['static_threshold_breach'], [
                        ['label' => 'Temperature', 'items' => ['Lowest' => 'temp_min_c', 'Highest' => 'temp_max_c']],
                        ['label' => 'CO₂', 'items' => ['Highest' => 'co2_max_ppm']],
                        ['label' => 'Weight', 'items' => ['Lowest' => 'weight_min_kg', 'Highest' => 'weight_max_kg']],
                    ], [
                        $this->field('temp_min_c', 'Lowest plausible temperature', '°C', -10, -50, 50, 0.1),
                        $this->field('temp_max_c', 'Highest plausible temperature', '°C', 60, 0, 150, 0.1),
                        $this->field('co2_max_ppm', 'Highest plausible CO₂', 'ppm', 5000, 400, 100000),
                        $this->field('weight_min_kg', 'Lowest plausible weight', 'kg', 5, 0, 500, 0.1),
                        $this->field('weight_max_kg', 'Highest plausible weight', 'kg', 120, 1, 1000, 0.1),
                    ], 'Humidity is fixed at 0 to 100 %.'),
                    $this->rule('frozen_sensor', self::RULE_DESCRIPTIONS['frozen_sensor'], [
                        ['label' => 'Repeated value', 'items' => ['Flag after' => 'stuck_sensor_reading_count']],
                    ], [
                        $this->field('stuck_sensor_reading_count', 'Identical readings in a row', 'readings in a row', 10, 3, 500),
                    ]),
                ],
            ],
            [
                'id' => 'colony-signals',
                'title' => 'Colony signals',
                'summary' => self::GROUP_SUMMARIES['Colony signals'],
                'icon' => 'bi-hexagon',
                'rules' => [
                    $this->rule('statistical_deviation', self::RULE_DESCRIPTIONS['statistical_deviation'], [
                        ['label' => 'Distance from the average', 'items' => ['Flag beyond' => 'zscore_stddev_threshold']],
                    ], [
                        $this->field('zscore_stddev_threshold', 'Distance from the average', 'standard deviations', 3, 1, 10, 0.1),
                    ], 'A lower number flags more readings, including more false alarms. The rule needs at least 10 readings in the 24 hours.'),
                ],
            ],
            [
                'id' => 'device-health',
                'title' => 'Device health',
                'summary' => self::GROUP_SUMMARIES['Device health'],
                'icon' => 'bi-cpu',
                'rules' => [
                    $this->rule('low_battery', 'Battery level reported by the unit.', [
                        ['label' => 'Battery level', 'items' => ['Low at or below' => 'low_battery_pct', 'Critical at or below' => 'critical_battery_pct']],
                    ], [
                        $this->field('low_battery_pct', 'Low battery level', '%', 20, 1, 100),
                        $this->field('critical_battery_pct', 'Critical battery level', '%', 5, 0, 99),
                    ], null, 'Battery'),
                    $this->rule('weak_signal', 'A brief dip is ignored; the signal must stay weak.', [
                        ['label' => 'Weak signal', 'items' => ['At or below' => 'weak_signal_rssi_dbm', 'For at least' => 'weak_signal_sustained_minutes']],
                    ], [
                        $this->field('weak_signal_rssi_dbm', 'Weak signal strength', 'dBm', -85, -150, 0),
                        $this->field('weak_signal_sustained_minutes', 'Minutes the signal must stay weak', 'minutes', 30, 0, 1440),
                    ], null, 'Signal'),
                    $this->rule('device_offline', 'How long a unit may stay silent, and how slowly it may report.', [
                        ['label' => 'Silence and pace', 'items' => ['Offline after' => 'device_offline_silence_minutes', 'Late when slower than' => 'submission_interval_multiplier']],
                    ], [
                        $this->field('device_offline_silence_minutes', 'Minutes of silence before offline', 'minutes', 120, 5, 10080),
                        $this->field('submission_interval_multiplier', 'Late-reporting multiplier', '× interval', 2, 1, 20, 0.1),
                    ], null, 'Connectivity'),
                    $this->rule('high_anomaly_rate', "The share of a unit's readings in the last hour that were marked suspect.", [
                        ['label' => 'Suspect share', 'items' => ['Flag above' => 'high_anomaly_rate_pct', 'Once it has sent' => 'high_anomaly_rate_min_readings']],
                    ], [
                        $this->field('high_anomaly_rate_pct', 'Share of suspect readings', '%', 20, 1, 100),
                        $this->field('high_anomaly_rate_min_readings', 'Readings needed before judging', 'readings', 10, 1, 1000),
                    ], null, 'Suspect Readings'),
                    $this->rule('storage_full', 'On-device storage.', [
                        ['label' => 'Storage used', 'items' => ['Full at or above' => 'storage_full_pct']],
                    ], [
                        $this->field('storage_full_pct', 'Storage full level', '%', 90, 1, 100),
                    ], null, 'Storage'),
                    $this->rule('reboot_loop', 'A unit that keeps restarting.', [
                        ['label' => 'Restarts', 'items' => ['Reboot loop at' => 'reboot_loop_count_per_hour']],
                    ], [
                        $this->field('reboot_loop_count_per_hour', 'Restarts within an hour', 'per hour', 3, 1, 100),
                    ], null, 'Restarts'),
                    $this->rule('firmware_outdated', 'Units on an older release are listed as outdated.', [
                        ['label' => 'Firmware', 'items' => ['Current release' => 'latest_firmware_version']],
                    ], [
                        ['key' => 'latest_firmware_version', 'label' => 'Current firmware release', 'unit' => null, 'default' => '0', 'type' => 'version', 'fleetOnly' => true],
                    ], 'For example 1.4.2. Enter 0 to turn the check off.', 'Firmware'),
                ],
            ],
        ];
    }

    private function rule(string $type, string $description, array $rows, array $fields, ?string $note = null, ?string $label = null): array
    {
        return [
            'type' => $type,
            'label' => $label ?? SensorAnomaly::labelFor($type),
            'icon' => (new SensorAnomaly(['anomaly_type' => $type]))->icon(),
            'description' => $description,
            'note' => $note,
            'rows' => $rows,
            'fields' => $fields,
        ];
    }

    /** A numeric limit. A step of 1 means whole numbers only. */
    private function field(string $key, string $label, string $unit, float|int $default, float|int $min, float|int $max, float|int $step = 1): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'unit' => $unit,
            'default' => (string) $default,
            'type' => 'number',
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'fleetOnly' => false,
        ];
    }
}

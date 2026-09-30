<?php

namespace Database\Seeders;

use App\Models\AlertThreshold;
use Illuminate\Database\Seeder;

/**
 * REQ-F-FAPI-24: Default alert thresholds, admin-editable afterward.
 */
class AlertThresholdSeeder extends Seeder
{
    public function run(): void
    {
        AlertThreshold::updateOrCreate(
            ['key' => 'feed_required_weight_kg'],
            ['value' => '15', 'description' => 'Minimum hive weight (kg) before a feed_required alert fires.']
        );

        AlertThreshold::updateOrCreate(
            ['key' => 'malfunction_threshold_placeholder'],
            ['value' => '', 'description' => 'Placeholder — pending domain-expert review per REQ-F-FAPI-24 note.']
        );

        AlertThreshold::updateOrCreate(
            ['key' => 'critical_event_threshold_placeholder'],
            ['value' => '', 'description' => 'Placeholder — pending domain-expert review per REQ-F-FAPI-24 note.']
        );

        // IoT Condition Monitoring, Layer 1 rules engine (SDD §4.4.7) —
        // global defaults, per-hive overridable for free via
        // AlertThreshold::getForHive().
        $anomalyRuleDefaults = [
            'temp_min_c' => ['-10', 'Minimum physically plausible hive temperature (°C).'],
            'temp_max_c' => ['60', 'Maximum physically plausible hive temperature (°C).'],
            'co2_max_ppm' => ['5000', 'Maximum physically plausible CO2 level (ppm).'],
            'weight_min_kg' => ['5', 'Minimum physically plausible hive weight (kg).'],
            'weight_max_kg' => ['120', 'Maximum physically plausible hive weight (kg).'],
            'stuck_sensor_reading_count' => ['10', 'Consecutive identical readings before a sensor is flagged as frozen/stuck.'],
            'zscore_stddev_threshold' => ['3', 'Standard deviations from the rolling mean before a reading is flagged as a statistical anomaly.'],
            'device_offline_silence_minutes' => ['120', 'Minutes of silence before a device is flagged offline.'],
            'submission_interval_multiplier' => ['2', 'A device is flagged late (submission_delay) when the median gap between its last 10 submissions exceeds this many times its expected interval.'],
            'low_battery_pct' => ['20', 'Battery level (%) at or below which a low_battery anomaly fires.'],
            'critical_battery_pct' => ['5', 'Battery level (%) at or below which a critical_battery anomaly fires.'],
            'weak_signal_rssi_dbm' => ['-85', 'Signal strength (dBm) at or below which a weak_signal anomaly fires.'],
            'weak_signal_sustained_minutes' => ['30', 'Minutes the signal must stay weak before a weak_signal anomaly fires.'],
            'high_anomaly_rate_pct' => ['20', 'Share of a device\'s readings in the last hour (%) that, once flagged suspect, raises high_anomaly_rate.'],
            'high_anomaly_rate_min_readings' => ['10', 'Readings a device must send in the hour before high_anomaly_rate is evaluated.'],
            'reboot_loop_count_per_hour' => ['3', 'Reboots within a 1-hour window before a reboot_loop anomaly fires.'],
            'storage_full_pct' => ['90', 'Storage usage (%) at or above which a storage_full anomaly fires.'],
            'telemetry_history_retention_days' => ['90', 'Days of iot_device_telemetry_history kept before PruneTelemetryHistory deletes them.'],
        ];

        foreach ($anomalyRuleDefaults as $key => [$value, $description]) {
            AlertThreshold::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'description' => $description]
            );
        }

        // Sensor Monitoring target bands shaded on the brood chamber charts.
        // firstOrCreate, so re-seeding keeps values an admin has tuned.
        $broodTargetDefaults = [
            'brood_temp_ideal_min_c' => ['32', 'Lower edge of the healthy brood chamber temperature band (°C).'],
            'brood_temp_ideal_max_c' => ['36', 'Upper edge of the healthy brood chamber temperature band (°C).'],
            'brood_humidity_ideal_min_pct' => ['50', 'Lower edge of the healthy brood chamber humidity band (%).'],
            'brood_humidity_ideal_max_pct' => ['70', 'Upper edge of the healthy brood chamber humidity band (%).'],
        ];

        foreach ($broodTargetDefaults as $key => [$value, $description]) {
            AlertThreshold::firstOrCreate(
                ['key' => $key, 'hive_id' => null],
                ['value' => $value, 'description' => $description]
            );
        }

        // Set by an admin at each firmware release, so never overwritten here.
        // "0" keeps the check off: no version is older than 0.
        AlertThreshold::firstOrCreate(
            ['key' => 'latest_firmware_version', 'hive_id' => null],
            ['value' => '0', 'description' => 'Current firmware release (e.g. 1.4.2). Devices on an older version raise firmware_outdated. 0 turns the check off.']
        );
    }
}
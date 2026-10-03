<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

/**
 * Throwaway rendering smoke test for the redesigned anomaly views — not
 * part of the permanent suite, just used to catch Blade runtime errors
 * (null handling, chart data shape, route resolution) with realistic,
 * varied data before calling the UI redesign done.
 */
class AnomalyUiSmokeTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private function actingAsMonitoringAdmin(): void
    {
        $admin = $this->actingAsAdminWithPermission('view-anomaly-analytics');

        foreach (['view-device-fleet', 'manage-iot-devices', 'view-hive-data'] as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $admin->givePermissionTo($permission);
        }
    }

    #[Test]
    public function all_anomaly_and_fleet_views_render_with_realistic_data(): void
    {
        $this->actingAsMonitoringAdmin();

        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);

        $hives = Hive::factory()->count(3)->create(['apiary_id' => $apiary->id]);
        $devices = $hives->map(fn ($hive) => IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]));

        // A device with no hive assignment at all, to hit the null-hive paths.
        $unassignedDevice = IotDevice::factory()->create([
            'hive_id' => null,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);

        $types = ['static_threshold_breach', 'frozen_sensor', 'statistical_deviation', 'low_battery', 'critical_battery', 'weak_signal', 'reboot_loop', 'storage_full', 'device_offline'];

        foreach ($devices as $i => $device) {
            foreach ($types as $j => $type) {
                SensorAnomaly::create([
                    'device_id' => $device->id,
                    'hive_id' => $hives[$i]->id,
                    'sensor_type' => in_array($type, ['static_threshold_breach', 'frozen_sensor', 'statistical_deviation'], true) ? 'temperature' : 'telemetry',
                    'anomaly_type' => $type,
                    'anomaly_score' => 1.0,
                    'record_value' => ['value' => 42],
                    'detection_layer' => 'rules',
                    'detected_at' => now()->subDays($j % 7)->subHours($i),
                    'resolved' => $j % 3 === 0,
                    'resolved_at' => $j % 3 === 0 ? now() : null,
                    'auto_resolved' => $j % 2 === 0,
                    'acknowledged_at' => $j % 4 === 1 ? now() : null,
                    'occurrences' => $j + 1,
                    'last_record_value' => $j % 2 === 0 ? ['value' => 43.5] : null,
                ]);
            }
        }

        // Unassigned-device anomaly, no hive.
        SensorAnomaly::create([
            'device_id' => $unassignedDevice->id,
            'hive_id' => null,
            'sensor_type' => 'telemetry',
            'anomaly_type' => 'low_battery',
            'anomaly_score' => 1.0,
            'record_value' => ['battery_level' => 10],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);

        $device = $devices->first();
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 45,
            'signal_strength' => -70,
            'storage_usage' => 60,
            'last_heartbeat_at' => now()->subMinutes(3),
        ]);

        foreach (range(0, 6) as $d) {
            IotDeviceTelemetryHistory::create([
                'device_id' => $device->id,
                'battery_level' => 90 - $d * 5,
                'signal_strength' => -50 - $d,
                'recorded_at' => now()->subDays(6 - $d),
            ]);
        }

        // A device with zero telemetry history at all, to hit the empty-state branch.
        $bareDevice = $devices->last();

        $this->get(route('admin.anomaly.dashboard'))->assertOk();
        $this->get(route('admin.anomaly.analytics', ['days' => 7]))->assertOk();
        $this->get(route('admin.anomaly.analytics', ['days' => 30]))->assertOk();
        $this->get(route('admin.devices.fleet'))->assertOk();
        $this->get(route('admin.devices.fleet', ['health' => 'offline', 'has_issues' => 1]))->assertOk();

        foreach (['hive', 'device', 'all'] as $category) {
            $this->get(route('admin.anomaly.anomalies.index', ['category' => $category]))->assertOk();
        }
        $this->get(route('admin.anomaly.anomalies.index', ['status' => 'unresolved', 'severity' => 'critical', 'from' => today()->subWeek()->toDateString()]))->assertOk();

        foreach (SensorAnomaly::all() as $anomaly) {
            $this->get(route('admin.anomaly.anomalies.show', $anomaly))->assertOk();
        }

        $this->get(route('admin.iot-devices.show', $device))->assertOk();
        $this->get(route('admin.iot-devices.show', $bareDevice))->assertOk();
        $this->get(route('admin.iot-devices.show', $unassignedDevice))->assertOk();
        $this->get(route('admin.hives.show', $hives->first()))->assertOk();
    }

    #[Test]
    public function all_views_render_with_zero_data(): void
    {
        $this->actingAsMonitoringAdmin();
        $device = IotDevice::factory()->create(['hardware_team_id' => IotHardwareTeam::factory()]);

        $this->get(route('admin.anomaly.dashboard'))->assertOk();
        $this->get(route('admin.anomaly.analytics'))->assertOk();
        $this->get(route('admin.anomaly.anomalies.index'))->assertOk();
        $this->get(route('admin.devices.fleet'))->assertOk();
        $this->get(route('admin.iot-devices.show', $device))->assertOk();
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class AnomalyControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private function makeAnomaly(string $sensorType = 'temperature', string $anomalyType = 'static_threshold_breach'): SensorAnomaly
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        return SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $hive->id,
            'sensor_type' => $sensorType,
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => ['value' => 1],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }

    #[Test]
    public function the_list_defaults_to_hive_conditions_and_can_switch_to_device_issues(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $hiveAnomaly = $this->makeAnomaly();
        $deviceIssue = $this->makeAnomaly('telemetry', 'low_battery');

        $this->get(route('admin.anomaly.anomalies.index'))
            ->assertOk()
            ->assertViewHas('anomalies', fn ($page) => $page->pluck('id')->all() === [$hiveAnomaly->id]);

        $this->get(route('admin.anomaly.anomalies.index', ['category' => 'device']))
            ->assertOk()
            ->assertViewHas('anomalies', fn ($page) => $page->pluck('id')->all() === [$deviceIssue->id]);
    }

    #[Test]
    public function an_admin_can_acknowledge_then_resolve_an_anomaly_with_a_note(): void
    {
        $admin = $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $anomaly = $this->makeAnomaly();

        $this->patch(route('admin.anomaly.anomalies.acknowledge', $anomaly))->assertRedirect();
        $this->assertSame($admin->id, $anomaly->fresh()->acknowledged_by);

        $this->patch(route('admin.anomaly.anomalies.resolve', $anomaly), ['note' => 'Hive shaded'])->assertRedirect();

        $this->assertDatabaseHas('sensor_anomalies', [
            'id' => $anomaly->id,
            'resolved' => true,
            'resolved_by' => $admin->id,
            'resolution_note' => 'Hive shaded',
            'auto_resolved' => false,
        ]);
    }

    #[Test]
    public function a_fleet_viewer_can_open_and_resolve_device_issues(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $issue = $this->makeAnomaly('telemetry', 'weak_signal');

        $this->get(route('admin.anomaly.anomalies.show', $issue))->assertOk();
        $this->patch(route('admin.anomaly.anomalies.resolve', $issue))->assertRedirect();

        $this->assertTrue($issue->fresh()->resolved);
    }

    #[Test]
    public function users_without_monitoring_permissions_cannot_see_or_change_anomalies(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $anomaly = $this->makeAnomaly();

        $this->get(route('admin.anomaly.anomalies.index'))->assertForbidden();
        $this->get(route('admin.anomaly.anomalies.show', $anomaly))->assertForbidden();
        $this->patch(route('admin.anomaly.anomalies.resolve', $anomaly))->assertForbidden();

        $this->assertFalse($anomaly->fresh()->resolved);
    }

    #[Test]
    public function the_device_fleet_page_reports_live_health_and_filters_by_it(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $team = IotHardwareTeam::factory()->create();

        $online = IotDevice::factory()->create(['hardware_team_id' => $team->id, 'hive_id' => null]);
        IotDeviceTelemetry::create(['device_id' => $online->id, 'battery_level' => 90, 'signal_strength' => -50, 'last_heartbeat_at' => now()]);

        $offline = IotDevice::factory()->create(['hardware_team_id' => $team->id, 'hive_id' => null]);
        IotDeviceTelemetry::create(['device_id' => $offline->id, 'last_heartbeat_at' => now()->subDay()]);

        $this->get(route('admin.devices.fleet'))
            ->assertOk()
            ->assertViewHas('kpis', fn ($kpis) => $kpis['online'] >= 1 && $kpis['offline'] >= 1);

        $this->get(route('admin.devices.fleet', ['health' => 'offline']))
            ->assertOk()
            ->assertViewHas('fleet', fn ($page) => $page->pluck('id')->contains($offline->id) && ! $page->pluck('id')->contains($online->id));
    }

    #[Test]
    public function the_device_fleet_page_is_permission_gated(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $this->get(route('admin.devices.fleet'))->assertForbidden();
    }
}

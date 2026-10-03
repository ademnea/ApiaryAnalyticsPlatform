<?php

namespace Tests\Feature\Admin;

use App\Models\Alert;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class SystemAlertControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private function makeAlerts(): array
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $anomaly = SensorAnomaly::recordOrTouch([
            'device_id' => $device->id,
            'hive_id' => $hive->id,
            'sensor_type' => 'telemetry',
            'anomaly_type' => 'low_battery',
            'anomaly_score' => 1.0,
            'record_value' => ['battery_level' => 12],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);

        $fromAnomaly = Alert::create([
            'farmer_id' => $farmer->id,
            'hive_id' => $hive->id,
            'source_anomaly_id' => $anomaly->id,
            'type' => 'low_battery',
            'message' => 'Battery low',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $feedAlert = Alert::create([
            'farmer_id' => $farmer->id,
            'hive_id' => $hive->id,
            'type' => 'feed_required',
            'message' => 'Feed the hive',
            'is_read' => true,
            'read_at' => now(),
            'created_at' => now()->subHours(2),
        ]);

        return [$fromAnomaly, $feedAlert];
    }

    #[Test]
    public function monitoring_admins_can_see_all_alerts_with_their_source_incident(): void
    {
        $admin = $this->actingAsAdminWithPermission('view-monitoring-dashboard');
        \Spatie\Permission\Models\Permission::findOrCreate('view-anomaly-analytics', 'web');
        $admin->givePermissionTo('view-anomaly-analytics');
        [$fromAnomaly, $feedAlert] = $this->makeAlerts();

        $this->get(route('admin.alerts.index'))
            ->assertOk()
            ->assertViewHas('alerts', fn ($page) => $page->total() === 2)
            ->assertViewHas('kpis', fn ($kpis) => $kpis['unread'] === 1 && $kpis['from_anomalies'] === 1)
            ->assertSee(route('admin.anomaly.anomalies.show', $fromAnomaly->source_anomaly_id));
    }

    #[Test]
    public function alerts_can_be_filtered_by_source_and_read_state(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');
        [$fromAnomaly, $feedAlert] = $this->makeAlerts();

        $this->get(route('admin.alerts.index', ['source' => 'anomaly']))
            ->assertOk()
            ->assertViewHas('alerts', fn ($page) => $page->pluck('id')->all() === [$fromAnomaly->id]);

        $this->get(route('admin.alerts.index', ['read' => 'read']))
            ->assertOk()
            ->assertViewHas('alerts', fn ($page) => $page->pluck('id')->all() === [$feedAlert->id]);
    }

    #[Test]
    public function users_without_monitoring_permissions_cannot_see_alerts(): void
    {
        $this->actingAsAdminWithPermission('manage-newsletter');

        $this->get(route('admin.alerts.index'))->assertForbidden();
    }
}

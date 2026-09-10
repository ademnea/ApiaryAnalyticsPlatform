<?php

namespace Tests\Feature\Admin;

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

class AnomalyDashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private function makeAnomaly(): SensorAnomaly
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        return SensorAnomaly::create([
            'device_id' => $device->id,
            'hive_id' => $hive->id,
            'sensor_type' => 'temperature',
            'anomaly_type' => 'static_threshold_breach',
            'anomaly_score' => 1.0,
            'record_value' => ['brood_section' => 75.0],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }

    #[Test]
    public function admin_with_permission_can_view_the_anomaly_dashboard(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $this->makeAnomaly();

        $response = $this->get(route('admin.anomaly.dashboard'));

        $response->assertOk();
        $response->assertViewHas('unresolvedCount', 1);
        $response->assertViewHas('recentAnomalies');
    }

    #[Test]
    public function user_without_permission_cannot_view_the_anomaly_dashboard(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $response = $this->get(route('admin.anomaly.dashboard'));

        $response->assertForbidden();
    }

    #[Test]
    public function admin_with_permission_can_view_the_analytics_page(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $this->makeAnomaly();

        $response = $this->get(route('admin.anomaly.analytics', ['days' => 7]));

        $response->assertOk();
        $response->assertViewHas('days', 7);
    }

    #[Test]
    public function admin_with_permission_can_view_the_device_detail_page(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $anomaly = $this->makeAnomaly();

        $response = $this->get(route('admin.anomaly.devices.show', $anomaly->device_id));

        $response->assertOk();
        $response->assertViewHas('anomalies');
    }

    #[Test]
    public function user_without_permission_cannot_view_the_device_detail_page(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $anomaly = $this->makeAnomaly();

        $response = $this->get(route('admin.anomaly.devices.show', $anomaly->device_id));

        $response->assertForbidden();
    }
}

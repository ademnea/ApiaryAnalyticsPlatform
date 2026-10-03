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
        $response->assertViewHas('openCount', 1);
        $response->assertViewHas('latestOpen');
    }

    #[Test]
    public function the_dashboard_groups_the_rules_into_sensor_faults_and_colony_signals(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $this->makeAnomaly();

        $this->get(route('admin.anomaly.dashboard'))
            ->assertOk()
            ->assertSee('Impossible Reading')
            ->assertSee('Temperature: -10 to 60 °C')
            ->assertSee('10 identical readings in a row')
            ->assertSee('More than 3 standard deviations from the average')
            ->assertDontSee('Static Threshold Breach')
            ->assertViewHas('ruleGroups', function (array $groups) {
                $rules = collect($groups)->flatMap(fn (array $group) => collect($group['rules'])->map(fn (array $rule) => $rule + ['group' => $group['title']]))->keyBy('type');

                return $rules['static_threshold_breach']['group'] === 'Sensor faults'
                    && $rules['static_threshold_breach']['openCount'] === 1
                    && $rules['static_threshold_breach']['hivesAffected'] === 1
                    && $rules['frozen_sensor']['openCount'] === 0
                    && $rules['statistical_deviation']['group'] === 'Colony signals'
                    && $rules['statistical_deviation']['notifies'] === [
                        ['recipient' => 'Admins', 'channels' => ['email']],
                        ['recipient' => 'Farmer', 'channels' => ['push']],
                    ];
            });
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
    public function device_issues_are_kept_off_the_hive_anomaly_dashboard(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        $anomaly = $this->makeAnomaly();
        SensorAnomaly::create([
            'device_id' => $anomaly->device_id,
            'hive_id' => $anomaly->hive_id,
            'sensor_type' => 'telemetry',
            'anomaly_type' => 'low_battery',
            'anomaly_score' => 1.0,
            'record_value' => ['battery_level' => 10],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);

        $response = $this->get(route('admin.anomaly.dashboard'));

        $response->assertOk();
        $response->assertViewHas('openCount', 1);
        $response->assertViewHas('openDeviceIssuesCount', 1);
    }

    #[Test]
    public function the_device_page_shows_its_health_and_anomalies(): void
    {
        $this->actingAsAdminWithPermission('manage-iot-devices');
        $anomaly = $this->makeAnomaly();

        $response = $this->get(route('admin.iot-devices.show', $anomaly->device_id));

        $response->assertOk();
        $response->assertViewHas('health');
        $response->assertViewHas('anomalies', fn ($anomalies) => $anomalies->contains('id', $anomaly->id));
    }
}

<?php

namespace Tests\Unit\Anomaly;

use App\Models\Alert;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnomalyAlertDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnomaly(Hive $hive, IotDevice $device, string $anomalyType = 'static_threshold_breach'): SensorAnomaly
    {
        return SensorAnomaly::create([
            'device_id' => $device->id,
            'hive_id' => $hive->id,
            'sensor_type' => 'temperature',
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => ['brood_section' => 75.0],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }

    #[Test]
    public function it_resolves_the_farmer_via_the_hive_apiary_farmer_chain_and_sets_source_anomaly_id(): void
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);
        $anomaly = $this->makeAnomaly($hive, $device);

        $alert = app(AnomalyAlertDispatchService::class)->dispatch($anomaly);

        $this->assertNotNull($alert);
        $this->assertEquals($farmer->id, $alert->farmer_id);
        $this->assertEquals($anomaly->id, $alert->source_anomaly_id);
        $this->assertTrue($anomaly->fresh()->alerted);
    }

    #[Test]
    public function it_returns_null_and_does_not_create_an_alert_when_the_apiary_has_no_farmer(): void
    {
        $apiary = Apiary::factory()->create(['farmer_id' => null]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);
        $anomaly = $this->makeAnomaly($hive, $device);

        $alert = app(AnomalyAlertDispatchService::class)->dispatch($anomaly);

        $this->assertNull($alert);
        $this->assertDatabaseCount('alerts', 0);
    }

    #[Test]
    public function it_suppresses_a_second_alert_of_the_same_anomaly_type_within_the_cooldown_window(): void
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $service = app(AnomalyAlertDispatchService::class);

        $first = $service->dispatch($this->makeAnomaly($hive, $device));
        $second = $service->dispatch($this->makeAnomaly($hive, $device));

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseCount('sensor_anomalies', 2); // both anomalies persist regardless
    }

    #[Test]
    public function it_does_not_suppress_a_different_anomaly_type_on_the_same_hive(): void
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $service = app(AnomalyAlertDispatchService::class);

        $service->dispatch($this->makeAnomaly($hive, $device, 'static_threshold_breach'));
        $second = $service->dispatch($this->makeAnomaly($hive, $device, 'frozen_sensor'));

        $this->assertNotNull($second);
        $this->assertDatabaseCount('alerts', 2);
    }

    #[Test]
    public function malfunction_and_critical_event_never_suppress(): void
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $service = app(AnomalyAlertDispatchService::class);

        // critical_battery maps to a 15-minute cooldown per Alert::cooldownMinutesFor(),
        // so a second one immediately after should still be suppressed...
        $service->dispatch($this->makeAnomaly($hive, $device, 'critical_battery'));
        $suppressed = $service->dispatch($this->makeAnomaly($hive, $device, 'critical_battery'));
        $this->assertNull($suppressed);

        // ...but device_offline has no cooldown at the Alert level (it's gated
        // by CheckDeviceHealth's unresolved-anomaly guard instead), so two
        // dispatches both alert.
        $first = $service->dispatch($this->makeAnomaly($hive, $device, 'device_offline'));
        $second = $service->dispatch($this->makeAnomaly($hive, $device, 'device_offline'));
        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    #[Test]
    public function alerts_type_collapses_unmapped_anomaly_types_into_the_data_anomaly_enum_value(): void
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $alert = app(AnomalyAlertDispatchService::class)->dispatch(
            $this->makeAnomaly($hive, $device, 'static_threshold_breach')
        );

        $this->assertEquals('data_anomaly', $alert->type);
    }
}

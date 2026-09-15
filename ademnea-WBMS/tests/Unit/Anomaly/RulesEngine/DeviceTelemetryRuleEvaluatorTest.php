<?php

namespace Tests\Unit\Anomaly\RulesEngine;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\RulesEngine\DeviceTelemetryRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceTelemetryRuleEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): IotDevice
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);

        return IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
    }

    #[Test]
    public function it_returns_null_for_healthy_telemetry(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 80,
            'signal_strength' => -50,
            'storage_usage' => 10,
        ]);

        $anomaly = (new DeviceTelemetryRuleEvaluator())->evaluate($telemetry, $device);

        $this->assertNull($anomaly);
    }

    #[Test]
    public function it_flags_critical_battery_before_low_battery(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 3, // below both low (20) and critical (5)
        ]);

        $anomaly = (new DeviceTelemetryRuleEvaluator())->evaluate($telemetry, $device);

        $this->assertNotNull($anomaly);
        $this->assertEquals('critical_battery', $anomaly->anomaly_type);
    }

    #[Test]
    public function it_flags_weak_signal(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 80,
            'signal_strength' => -90,
        ]);

        $anomaly = (new DeviceTelemetryRuleEvaluator())->evaluate($telemetry, $device);

        $this->assertNotNull($anomaly);
        $this->assertEquals('weak_signal', $anomaly->anomaly_type);
    }

    #[Test]
    public function it_flags_storage_full(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'storage_usage' => 95,
        ]);

        $anomaly = (new DeviceTelemetryRuleEvaluator())->evaluate($telemetry, $device);

        $this->assertNotNull($anomaly);
        $this->assertEquals('storage_full', $anomaly->anomaly_type);
    }

    #[Test]
    public function a_recovered_battery_auto_resolves_the_open_incident(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create(['device_id' => $device->id, 'battery_level' => 15]);
        $evaluator = new DeviceTelemetryRuleEvaluator();

        $low = $evaluator->evaluate($telemetry, $device);
        $this->assertEquals('low_battery', $low->anomaly_type);

        $telemetry->update(['battery_level' => 85]);
        $this->assertNull($evaluator->evaluate($telemetry, $device));

        $fresh = $low->fresh();
        $this->assertTrue($fresh->resolved);
        $this->assertTrue($fresh->auto_resolved);
    }

    #[Test]
    public function repeated_violations_touch_one_incident_and_separate_problems_get_their_own(): void
    {
        $device = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create(['device_id' => $device->id, 'battery_level' => 15, 'signal_strength' => -90]);
        $evaluator = new DeviceTelemetryRuleEvaluator();

        $evaluator->evaluateAll($telemetry, $device);
        $second = $evaluator->evaluateAll($telemetry, $device);

        $this->assertCount(2, $second);
        $this->assertFalse($second[0]->wasRecentlyCreated);
        $this->assertDatabaseCount('sensor_anomalies', 2);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'low_battery', 'occurrences' => 2]);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'weak_signal', 'occurrences' => 2]);
    }

    #[Test]
    public function an_unassigned_device_is_evaluated_against_global_thresholds(): void
    {
        $device = IotDevice::factory()->create([
            'hive_id' => null,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
        $telemetry = IotDeviceTelemetry::create(['device_id' => $device->id, 'battery_level' => 3]);

        $anomaly = (new DeviceTelemetryRuleEvaluator())->evaluate($telemetry, $device);

        $this->assertEquals('critical_battery', $anomaly->anomaly_type);
        $this->assertNull($anomaly->hive_id);
    }
}

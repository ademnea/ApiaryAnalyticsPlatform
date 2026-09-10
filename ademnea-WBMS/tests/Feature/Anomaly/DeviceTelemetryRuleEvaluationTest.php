<?php

namespace Tests\Feature\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\IotHeartbeatProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceTelemetryRuleEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): array
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $device = IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);

        return [$hive, $device];
    }

    #[Test]
    public function a_low_battery_heartbeat_creates_an_anomaly_an_alert_and_a_history_row(): void
    {
        [$hive, $device] = $this->makeDevice();

        app(IotHeartbeatProcessingService::class)->store($device, ['battery_level' => 3]);

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'critical_battery',
        ]);

        $this->assertDatabaseHas('alerts', [
            'hive_id' => $hive->id,
        ]);

        $this->assertDatabaseHas('iot_device_telemetry_history', [
            'device_id' => $device->id,
        ]);
    }

    #[Test]
    public function healthy_telemetry_produces_no_anomaly_but_still_records_history(): void
    {
        [$hive, $device] = $this->makeDevice();

        app(IotHeartbeatProcessingService::class)->store($device, [
            'battery_level' => 90,
            'signal_strength' => -40,
            'storage_usage' => 5,
        ]);

        $this->assertDatabaseCount('sensor_anomalies', 0);
        $this->assertDatabaseCount('iot_device_telemetry_history', 1);
    }
}

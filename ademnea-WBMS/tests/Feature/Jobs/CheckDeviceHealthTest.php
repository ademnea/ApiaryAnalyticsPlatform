<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckDeviceHealth;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckDeviceHealthTest extends TestCase
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

    private function runJob(): void
    {
        (new CheckDeviceHealth())->handle(app(AnomalyAlertDispatchService::class));
    }

    #[Test]
    public function a_stale_heartbeat_beyond_the_silence_threshold_creates_a_device_offline_anomaly(): void
    {
        [, $device] = $this->makeDevice();
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'last_heartbeat_at' => now()->subMinutes(200),
            'last_data_received_at' => now()->subMinutes(200),
        ]);

        $this->runJob();

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'device_offline',
            'resolved' => false,
        ]);
        $this->assertDatabaseHas('alerts', ['hive_id' => $device->fresh()->hive_id]);

        $telemetry = IotDeviceTelemetry::where('device_id', $device->id)->first();
        $this->assertGreaterThanOrEqual(120, $telemetry->data_gap_minutes);
    }

    #[Test]
    public function it_does_not_duplicate_the_anomaly_on_a_second_run_while_still_offline(): void
    {
        [, $device] = $this->makeDevice();
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'last_heartbeat_at' => now()->subMinutes(200),
            'last_data_received_at' => now()->subMinutes(200),
        ]);

        $this->runJob();
        $this->runJob();

        $this->assertDatabaseCount('sensor_anomalies', 1);
    }

    #[Test]
    public function a_fresh_heartbeat_resolves_the_open_anomaly(): void
    {
        [, $device] = $this->makeDevice();
        $telemetry = IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'last_heartbeat_at' => now()->subMinutes(200),
            'last_data_received_at' => now()->subMinutes(200),
        ]);

        $this->runJob();
        $telemetry->update(['last_heartbeat_at' => now(), 'last_data_received_at' => now()]);
        $this->runJob();

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'device_offline',
            'resolved' => true,
        ]);
    }

    #[Test]
    public function a_healthy_device_is_left_untouched(): void
    {
        [, $device] = $this->makeDevice();
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'last_heartbeat_at' => now(),
            'last_data_received_at' => now(),
        ]);

        $this->runJob();

        $this->assertDatabaseCount('sensor_anomalies', 0);
    }
}

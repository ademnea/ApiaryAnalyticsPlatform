<?php

namespace Tests\Feature\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\IotHeartbeatProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeartbeatTimestampTest extends TestCase
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

    private function heartbeat(IotDevice $device, array $payload, ?string $receivedAt = null): void
    {
        app(IotHeartbeatProcessingService::class)->store($device, $payload, $receivedAt);
    }

    #[Test]
    public function a_heartbeat_is_stamped_with_its_recorded_at_not_the_processing_time(): void
    {
        $device = $this->makeDevice();
        $sentAt = now()->subHours(3)->startOfMinute();

        $this->heartbeat($device, ['battery_level' => 80, 'recorded_at' => $sentAt->toIso8601String()]);

        $this->assertTrue(IotDeviceTelemetry::where('device_id', $device->id)->value('last_heartbeat_at')->equalTo($sentAt));
        $this->assertTrue(IotDeviceTelemetryHistory::where('device_id', $device->id)->value('recorded_at')->equalTo($sentAt));
    }

    #[Test]
    public function without_recorded_at_the_gateway_receive_time_is_used(): void
    {
        $device = $this->makeDevice();
        $receivedAt = now()->subMinutes(20)->startOfMinute();

        $this->heartbeat($device, ['battery_level' => 80], $receivedAt->toIso8601String());

        $this->assertTrue(IotDeviceTelemetryHistory::where('device_id', $device->id)->value('recorded_at')->equalTo($receivedAt));
    }

    #[Test]
    public function an_out_of_order_heartbeat_is_kept_in_history_but_does_not_overwrite_current_state(): void
    {
        $device = $this->makeDevice();

        $this->heartbeat($device, ['battery_level' => 70, 'recorded_at' => now()->subMinutes(5)->toIso8601String()]);
        $this->heartbeat($device, ['battery_level' => 90, 'recorded_at' => now()->subMinutes(30)->toIso8601String()]);

        $this->assertEquals(70, IotDeviceTelemetry::where('device_id', $device->id)->value('battery_level'));
        $this->assertEqualsCanonicalizing(
            [70.0, 90.0],
            IotDeviceTelemetryHistory::where('device_id', $device->id)->pluck('battery_level')->all(),
        );
    }

    #[Test]
    public function a_stale_heartbeat_does_not_open_or_resolve_incidents(): void
    {
        $device = $this->makeDevice();

        // Current state: low battery → incident open.
        $this->heartbeat($device, ['battery_level' => 15, 'recorded_at' => now()->subMinutes(5)->toIso8601String()]);
        // An older, healthy heartbeat arrives late — must not auto-resolve it.
        $this->heartbeat($device, ['battery_level' => 95, 'recorded_at' => now()->subHour()->toIso8601String()]);

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'low_battery',
            'resolved' => false,
            'occurrences' => 1,
        ]);
        $this->assertSame(1, SensorAnomaly::count());
    }

    #[Test]
    public function a_future_timestamp_is_clamped_to_now(): void
    {
        $device = $this->makeDevice();

        $this->heartbeat($device, ['battery_level' => 80, 'recorded_at' => now()->addDay()->toIso8601String()]);

        $this->assertFalse(IotDeviceTelemetry::where('device_id', $device->id)->value('last_heartbeat_at')->isFuture());
    }
}

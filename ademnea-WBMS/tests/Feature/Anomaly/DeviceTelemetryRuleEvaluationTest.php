<?php

namespace Tests\Feature\Anomaly;

use App\Models\AlertThreshold;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\IotIngestionLog;
use App\Models\NotificationLog;
use App\Services\IotHeartbeatProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
    public function a_critical_battery_heartbeat_creates_an_anomaly_notifies_the_hardware_team_and_records_history(): void
    {
        [, $device] = $this->makeDevice();

        app(IotHeartbeatProcessingService::class)->store($device, ['battery_level' => 3]);

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'critical_battery',
        ]);

        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'hardware_team', 'channel' => 'email', 'status' => 'sent']);
        // SMS isn't configured in tests, so it's on record as skipped.
        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'hardware_team', 'channel' => 'sms', 'status' => 'skipped']);

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

    #[Test]
    public function a_malformed_heartbeat_is_rejected_whole_and_the_hardware_team_emailed_once(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotHeartbeatProcessingService::class);

        $service->store($device, ['battery_level' => 140, 'signal_strength' => -60]);
        $service->store($device, ['battery_level' => 'full']);

        $this->assertDatabaseCount('iot_device_telemetry', 0);
        $this->assertDatabaseCount('iot_device_telemetry_history', 0);
        $this->assertSame(2, IotIngestionLog::where('device_id', $device->id)->where('outcome', 'rejected_validation')->count());
        $this->assertArrayHasKey('battery_level', IotIngestionLog::first()->validation_errors);

        // The second bad heartbeat within the hour is logged but not emailed again.
        $this->assertSame(1, NotificationLog::where('type', 'malformed_heartbeat')->where('recipient_type', 'hardware_team')->count());
    }

    #[Test]
    public function weak_signal_is_raised_only_once_it_has_lasted_30_minutes(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotHeartbeatProcessingService::class);

        $service->store($device, ['signal_strength' => -95, 'recorded_at' => now()->subMinutes(20)->toIso8601String()]);
        $service->store($device, ['signal_strength' => -95, 'recorded_at' => now()->subMinutes(5)->toIso8601String()]);
        $this->assertDatabaseMissing('sensor_anomalies', ['anomaly_type' => 'weak_signal']);

        $this->travel(15)->minutes();
        $service->store($device, ['signal_strength' => -95, 'recorded_at' => now()->toIso8601String()]);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'weak_signal', 'resolved' => false]);

        $service->store($device, ['signal_strength' => -60, 'recorded_at' => now()->addMinute()->toIso8601String()]);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'weak_signal', 'resolved' => true]);
    }

    #[Test]
    public function a_brief_dip_in_signal_raises_nothing(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotHeartbeatProcessingService::class);

        $service->store($device, ['signal_strength' => -95, 'recorded_at' => now()->subMinutes(45)->toIso8601String()]);
        $service->store($device, ['signal_strength' => -60, 'recorded_at' => now()->subMinutes(30)->toIso8601String()]);
        $service->store($device, ['signal_strength' => -95, 'recorded_at' => now()->toIso8601String()]);

        $this->assertDatabaseMissing('sensor_anomalies', ['anomaly_type' => 'weak_signal']);
    }

    #[Test]
    public function outdated_firmware_is_flagged_once_a_latest_version_is_set(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotHeartbeatProcessingService::class);

        $service->store($device, ['firmware_version' => 'v1.2.0']);
        $this->assertDatabaseMissing('sensor_anomalies', ['anomaly_type' => 'firmware_outdated']);

        AlertThreshold::create(['key' => 'latest_firmware_version', 'value' => '1.4.2']);
        Cache::flush();

        $service->store($device, ['firmware_version' => 'v1.2.0']);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'firmware_outdated', 'resolved' => false]);

        $service->store($device, ['firmware_version' => '1.4.2']);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'firmware_outdated', 'resolved' => true]);
    }
}

<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckDeviceHealth;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveHumidity;
use App\Models\HiveTemperature;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use App\Services\IotSensorIngestionService;
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
        $this->assertDatabaseHas('notification_logs', ['type' => 'device_offline', 'recipient_type' => 'hardware_team', 'channel' => 'email']);

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

    /**
     * 11 readings $gapMinutes apart, newest now. The job takes the fastest
     * sensor stream as the device's rhythm.
     *
     * @param  class-string<HiveTemperature|HiveHumidity>  $model
     */
    private function readingsEvery(IotDevice $device, int $gapMinutes, string $model = HiveTemperature::class): void
    {
        foreach (range(0, 10) as $i) {
            $model::create([
                'hive_id' => $device->hive_id,
                'device_id' => $device->id,
                'brood_section' => 35.0,
                'suspect' => false,
                'recorded_at' => now()->subMinutes($i * $gapMinutes),
                'created_at' => now(),
            ]);
        }
    }

    #[Test]
    public function a_device_reporting_at_more_than_twice_its_expected_interval_is_flagged_late(): void
    {
        [, $device] = $this->makeDevice();
        $device->update(['expected_interval_minutes' => 5]);
        $this->readingsEvery($device, 12); // median 12 > 2 × 5
        IotDeviceTelemetry::create(['device_id' => $device->id, 'last_data_received_at' => now()]);

        $this->runJob();
        $this->runJob();

        $this->assertDatabaseCount('sensor_anomalies', 1);
        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'submission_delay',
            'resolved' => false,
            'occurrences' => 2,
        ]);
        $this->assertEquals(12.0, IotDeviceTelemetry::where('device_id', $device->id)->value('submission_interval_actual'));
        // Data gap: the farmer is alerted, once.
        $this->assertDatabaseCount('alerts', 1);
    }

    #[Test]
    public function reporting_on_schedule_is_not_late(): void
    {
        [, $device] = $this->makeDevice();
        $device->update(['expected_interval_minutes' => 5]);
        $this->readingsEvery($device, 5);
        IotDeviceTelemetry::create(['device_id' => $device->id, 'last_data_received_at' => now()]);

        $this->runJob();

        $this->assertDatabaseCount('sensor_anomalies', 0);
        $this->assertEquals(5.0, IotDeviceTelemetry::where('device_id', $device->id)->value('submission_interval_actual'));
    }

    #[Test]
    public function going_offline_closes_the_late_incident_and_recovery_closes_offline_and_tells_staff(): void
    {
        [, $device] = $this->makeDevice();
        $device->update(['expected_interval_minutes' => 5]);
        $this->readingsEvery($device, 12);
        $telemetry = IotDeviceTelemetry::create(['device_id' => $device->id, 'last_data_received_at' => now()]);

        $this->runJob();
        $telemetry->update(['last_data_received_at' => now()->subMinutes(200)]);
        $this->runJob();

        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'submission_delay', 'resolved' => true, 'auto_resolved' => true]);
        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'device_offline', 'resolved' => false]);

        // Back on schedule and heard from.
        $this->readingsEvery($device, 5, HiveHumidity::class);
        $telemetry->update(['last_data_received_at' => now()]);
        $this->runJob();

        $this->assertSame(0, SensorAnomaly::open()->count());
        $this->assertDatabaseHas('notification_logs', ['type' => 'device_recovered', 'recipient_type' => 'hardware_team', 'channel' => 'email']);
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

    #[Test]
    public function a_device_that_sends_readings_but_no_heartbeats_is_not_reported_offline(): void
    {
        [, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
        ]);

        $this->runJob();

        $this->assertDatabaseMissing('sensor_anomalies', ['device_id' => $device->id, 'anomaly_type' => 'device_offline']);
        $this->assertDatabaseMissing('sensor_anomalies', ['device_id' => $device->id, 'anomaly_type' => 'submission_delay']);
    }
}

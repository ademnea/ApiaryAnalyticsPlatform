<?php

namespace Tests\Feature\Anomaly;

use App\Events\SensorRecordReceived;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotHardwareTeam;
use App\Models\IotIngestionLog;
use App\Models\SensorAnomaly;
use App\Services\IotSensorIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SensorReadingRuleEvaluationTest extends TestCase
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

        return [$hive, $device, $farmer];
    }

    #[Test]
    public function an_out_of_range_reading_is_still_stored_and_also_flagged_and_alerted(): void
    {
        [$hive, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 75.0], // above the seeded temp_max_c=60
        ]);

        // Data-integrity guarantee (SDD §4.4.9(a)): the reading is stored
        // and ingestion accepted regardless of rule evaluation outcome.
        $this->assertDatabaseCount('hive_temperatures', 1);
        $this->assertDatabaseHas('iot_ingestion_logs', [
            'device_id' => $device->id,
            'outcome' => 'accepted',
        ]);

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'hive_id' => $hive->id,
            'anomaly_type' => 'static_threshold_breach',
        ]);

        // A sensor fault: the hardware team is told, the farmer isn't.
        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'hardware_team',
            'channel' => 'email',
            'type' => 'static_threshold_breach',
        ]);
        $this->assertDatabaseCount('alerts', 0);
    }

    #[Test]
    public function an_in_range_reading_produces_no_anomaly(): void
    {
        [$hive, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
        ]);

        $this->assertDatabaseCount('sensor_anomalies', 0);
        $this->assertDatabaseCount('alerts', 0);
    }

    #[Test]
    public function every_reading_updates_the_rolling_stats_regardless_of_anomaly_outcome(): void
    {
        [$hive, $device] = $this->makeDevice();
        $service = app(IotSensorIngestionService::class);

        foreach ([35.0, 36.0, 34.0] as $i => $value) {
            $service->store($device, [
                'sensor_type' => 'temperature',
                'recorded_at' => now()->addSeconds($i)->toIso8601String(),
                'reading' => ['brood_section' => $value],
            ]);
        }

        $this->assertDatabaseHas('hive_rolling_stats', [
            'hive_id' => $hive->id,
            'sensor_type' => 'temperature',
            'channel' => 'brood_section',
            'sample_count' => 3,
        ]);
    }

    #[Test]
    public function a_reading_from_an_unassigned_device_is_logged_as_rejected_instead_of_throwing(): void
    {
        Event::fake([SensorRecordReceived::class]);

        $device = IotDevice::factory()->create([
            'hive_id' => null,
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);

        // Must return normally: an exception here makes the queue worker
        // retry a message that can never succeed.
        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
        ]);

        $this->assertDatabaseCount('hive_temperatures', 0);
        $this->assertDatabaseHas('iot_ingestion_logs', ['device_id' => $device->id, 'outcome' => 'rejected_validation']);
        $this->assertDatabaseMissing('iot_ingestion_logs', ['device_id' => $device->id, 'outcome' => 'accepted']);
        Event::assertNotDispatched(SensorRecordReceived::class);
    }

    #[Test]
    public function an_unknown_sensor_type_is_only_rejected_never_also_accepted(): void
    {
        Event::fake([SensorRecordReceived::class]);

        [, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'pressure',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['pressure_hpa' => 1013],
        ]);

        $this->assertDatabaseHas('iot_ingestion_logs', ['device_id' => $device->id, 'outcome' => 'rejected_validation']);
        $this->assertDatabaseMissing('iot_ingestion_logs', ['device_id' => $device->id, 'outcome' => 'accepted']);
        Event::assertNotDispatched(SensorRecordReceived::class);
    }

    #[Test]
    public function an_out_of_range_reading_is_marked_suspect_and_an_in_range_one_is_not(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotSensorIngestionService::class);

        $service->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->subMinute()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
        ]);
        $service->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 75.0],
        ]);

        $this->assertDatabaseHas('hive_temperatures', ['brood_section' => 35.0, 'suspect' => false]);
        $this->assertDatabaseHas('hive_temperatures', ['brood_section' => 75.0, 'suspect' => true]);
    }

    #[Test]
    public function the_reading_that_reveals_a_frozen_sensor_is_marked_suspect(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotSensorIngestionService::class);

        foreach (range(1, 10) as $i) {
            $service->store($device, [
                'sensor_type' => 'weight',
                'recorded_at' => now()->subMinutes(10 - $i)->toIso8601String(),
                'reading' => ['weight_kg' => 42.5],
            ]);
        }

        $this->assertDatabaseHas('sensor_anomalies', ['device_id' => $device->id, 'anomaly_type' => 'frozen_sensor']);
        $this->assertSame(1, HiveWeight::where('suspect', true)->count());
        $this->assertTrue(HiveWeight::latest('recorded_at')->first()->suspect);
    }

    #[Test]
    public function a_statistical_outlier_opens_an_incident_but_is_not_marked_suspect(): void
    {
        [, $device] = $this->makeDevice();
        $service = app(IotSensorIngestionService::class);

        // A varied baseline past the z-score warm-up of 10 samples.
        foreach (range(1, 12) as $i) {
            $service->store($device, [
                'sensor_type' => 'weight',
                'recorded_at' => now()->subMinutes(20 - $i)->toIso8601String(),
                'reading' => ['weight_kg' => [40.0, 41.0, 42.0][$i % 3]],
            ]);
        }

        // Far from the ~41 kg mean, but inside the plausible 5-120 kg range.
        $service->store($device, [
            'sensor_type' => 'weight',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['weight_kg' => 60.0],
        ]);

        $this->assertDatabaseHas('sensor_anomalies', ['device_id' => $device->id, 'anomaly_type' => 'statistical_deviation']);
        $this->assertDatabaseHas('hive_weights', ['weight_kg' => 60.0, 'suspect' => false]);
    }

    #[Test]
    public function an_accepted_reading_records_when_the_device_last_sent_data(): void
    {
        [, $device] = $this->makeDevice();

        // An old recorded_at, as in a backlog upload: contact is still now.
        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->subDays(3)->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
        ]);

        $telemetry = IotDeviceTelemetry::where('device_id', $device->id)->first();

        $this->assertNotNull($telemetry);
        $this->assertTrue($telemetry->last_data_received_at->gte(now()->subMinute()));
        $this->assertNull($telemetry->last_heartbeat_at);
    }

    #[Test]
    public function piggybacked_device_meta_updates_the_device_telemetry(): void
    {
        [, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
            'device_meta' => ['battery_level' => 64.5, 'signal_strength' => -71, 'firmware_version' => '1.4.2'],
        ]);

        $telemetry = IotDeviceTelemetry::where('device_id', $device->id)->first();
        $this->assertEquals(64.5, $telemetry->battery_level);
        $this->assertEquals(-71, $telemetry->signal_strength);
        $this->assertSame('1.4.2', $device->fresh()->firmware_version);
    }

    #[Test]
    public function malformed_device_meta_fields_are_dropped_and_the_reading_still_stored(): void
    {
        [, $device] = $this->makeDevice();

        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
            'device_meta' => ['battery_level' => 'high', 'signal_strength' => -71],
        ]);

        $this->assertDatabaseCount('hive_temperatures', 1);
        $telemetry = IotDeviceTelemetry::where('device_id', $device->id)->first();
        $this->assertNull($telemetry->battery_level);
        $this->assertEquals(-71, $telemetry->signal_strength);
    }

    #[Test]
    public function device_meta_older_than_the_last_heartbeat_does_not_overwrite_it(): void
    {
        [, $device] = $this->makeDevice();
        IotDeviceTelemetry::create(['device_id' => $device->id, 'battery_level' => 80, 'last_heartbeat_at' => now()]);

        // A backlog reading from yesterday.
        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->subDay()->toIso8601String(),
            'reading' => ['brood_section' => 35.0],
            'device_meta' => ['battery_level' => 30],
        ]);

        $this->assertEquals(80, IotDeviceTelemetry::where('device_id', $device->id)->value('battery_level'));
    }
}

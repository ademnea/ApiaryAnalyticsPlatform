<?php

namespace Tests\Feature\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Models\User;
use App\Services\IotSensorIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnomalyIncidentLifecycleTest extends TestCase
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

    private function attributes(IotDevice $device, array $overrides = []): array
    {
        return $overrides + [
            'device_id' => $device->id,
            'hive_id' => $device->hive_id,
            'sensor_type' => 'temperature',
            'anomaly_type' => 'static_threshold_breach',
            'anomaly_score' => 1.0,
            'record_value' => ['brood_section' => 75.0],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ];
    }

    private function storeTemperature(IotDevice $device, float $value, int $offsetSeconds = 0): void
    {
        app(IotSensorIngestionService::class)->store($device, [
            'sensor_type' => 'temperature',
            'recorded_at' => now()->addSeconds($offsetSeconds)->toIso8601String(),
            'reading' => ['brood_section' => $value],
        ]);
    }

    #[Test]
    public function a_repeat_detection_touches_the_open_incident_instead_of_inserting(): void
    {
        [, $device] = $this->makeDevice();

        $first = SensorAnomaly::recordOrTouch($this->attributes($device));
        $second = SensorAnomaly::recordOrTouch($this->attributes($device, ['record_value' => ['brood_section' => 80.0]]));

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('sensor_anomalies', 1);

        $fresh = $first->fresh();
        $this->assertSame(2, $fresh->occurrences);
        $this->assertEquals(['brood_section' => 75.0], $fresh->record_value);
        $this->assertEquals(['brood_section' => 80.0], $fresh->last_record_value);
    }

    #[Test]
    public function a_recurrence_after_resolution_opens_a_new_incident(): void
    {
        [, $device] = $this->makeDevice();

        $first = SensorAnomaly::recordOrTouch($this->attributes($device));
        $first->resolve(null);

        $second = SensorAnomaly::recordOrTouch($this->attributes($device));

        $this->assertTrue($second->wasRecentlyCreated);
        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseCount('sensor_anomalies', 2);
    }

    #[Test]
    public function different_anomaly_types_are_separate_incidents(): void
    {
        [, $device] = $this->makeDevice();

        SensorAnomaly::recordOrTouch($this->attributes($device));
        SensorAnomaly::recordOrTouch($this->attributes($device, ['anomaly_type' => 'frozen_sensor']));

        $this->assertDatabaseCount('sensor_anomalies', 2);
    }

    #[Test]
    public function acknowledge_and_manual_resolve_record_who_and_why(): void
    {
        [, $device] = $this->makeDevice();
        $user = User::factory()->create();
        $anomaly = SensorAnomaly::recordOrTouch($this->attributes($device));

        $anomaly->acknowledge($user);
        $this->assertSame('acknowledged', $anomaly->fresh()->status());

        $anomaly->resolve($user, 'Sensor recalibrated');
        $fresh = $anomaly->fresh();

        $this->assertSame('resolved', $fresh->status());
        $this->assertSame($user->id, $fresh->resolved_by);
        $this->assertSame('Sensor recalibrated', $fresh->resolution_note);
        $this->assertFalse($fresh->auto_resolved);
    }

    #[Test]
    public function repeated_out_of_range_readings_create_one_incident_and_one_alert(): void
    {
        [$hive, $device] = $this->makeDevice();

        $this->storeTemperature($device, 75.0, 0);
        $this->storeTemperature($device, 78.0, 1);
        $this->storeTemperature($device, 80.0, 2);

        $this->assertDatabaseCount('sensor_anomalies', 1);
        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'static_threshold_breach',
            'occurrences' => 3,
            'resolved' => false,
        ]);
        $this->assertDatabaseCount('alerts', 1);
    }

    #[Test]
    public function a_reading_back_in_range_auto_resolves_the_incident(): void
    {
        [, $device] = $this->makeDevice();

        $this->storeTemperature($device, 75.0, 0);
        $this->storeTemperature($device, 35.0, 1);

        $this->assertDatabaseHas('sensor_anomalies', [
            'device_id' => $device->id,
            'anomaly_type' => 'static_threshold_breach',
            'resolved' => true,
            'auto_resolved' => true,
        ]);
    }
}

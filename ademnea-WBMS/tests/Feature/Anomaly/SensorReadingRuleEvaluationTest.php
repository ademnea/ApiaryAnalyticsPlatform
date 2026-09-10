<?php

namespace Tests\Feature\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\IotIngestionLog;
use App\Models\SensorAnomaly;
use App\Services\IotSensorIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertDatabaseHas('alerts', [
            'hive_id' => $hive->id,
            'type' => 'data_anomaly',
        ]);
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
}

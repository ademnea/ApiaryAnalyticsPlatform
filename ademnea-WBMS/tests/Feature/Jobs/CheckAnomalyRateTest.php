<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckAnomalyRate;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckAnomalyRateTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): IotDevice
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);

        return IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);
    }

    /** $total weight readings in the last hour, the first $suspect of them flagged. */
    private function readings(IotDevice $device, int $total, int $suspect, int $offset = 0): void
    {
        foreach (range(1, $total) as $i) {
            HiveWeight::create([
                'hive_id' => $device->hive_id,
                'device_id' => $device->id,
                'weight_kg' => 40 + $i,
                'suspect' => $i <= $suspect,
                'recorded_at' => now()->subMinutes($offset + $i),
                'created_at' => now(),
            ]);
        }
    }

    private function runJob(): void
    {
        (new CheckAnomalyRate())->handle(app(AnomalyAlertDispatchService::class));
    }

    #[Test]
    public function more_than_20_percent_flagged_in_an_hour_raises_high_anomaly_rate(): void
    {
        $device = $this->makeDevice();
        $this->readings($device, 10, 3);

        $this->runJob();

        $anomaly = SensorAnomaly::where('anomaly_type', 'high_anomaly_rate')->first();
        $this->assertNotNull($anomaly);
        $this->assertSame(SensorAnomaly::DEVICE_SENSOR_TYPE, $anomaly->sensor_type);
        $this->assertEquals(['flagged_readings' => 3, 'readings' => 10, 'rate_pct' => 30], $anomaly->record_value);
    }

    #[Test]
    public function too_few_readings_are_not_judged(): void
    {
        $device = $this->makeDevice();
        $this->readings($device, 3, 2);

        $this->runJob();

        $this->assertDatabaseCount('sensor_anomalies', 0);
    }

    #[Test]
    public function the_incident_closes_once_the_rate_drops_back(): void
    {
        $device = $this->makeDevice();
        $this->readings($device, 10, 3);
        $this->runJob();

        // Ten clean readings dilute the rate to 3 in 20: 15 %.
        $this->readings($device, 10, 0, offset: 10);
        $this->runJob();

        $this->assertDatabaseHas('sensor_anomalies', ['anomaly_type' => 'high_anomaly_rate', 'resolved' => true, 'auto_resolved' => true]);
    }
}

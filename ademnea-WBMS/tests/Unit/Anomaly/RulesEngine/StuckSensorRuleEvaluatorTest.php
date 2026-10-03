<?php

namespace Tests\Unit\Anomaly\RulesEngine;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\RulesEngine\StuckSensorRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StuckSensorRuleEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadingContext(): array
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
    public function it_returns_null_when_readings_vary(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        $reading = null;
        foreach ([10, 11, 10, 12, 10, 11, 10, 12, 10, 11] as $i => $weight) {
            $reading = HiveWeight::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'weight_kg' => $weight,
                'suspect' => false,
                'recorded_at' => now()->subMinutes(10 - $i),
                'created_at' => now(),
            ]);
        }

        $anomaly = (new StuckSensorRuleEvaluator())->evaluate($reading, 'weight');

        $this->assertNull($anomaly);
    }

    #[Test]
    public function it_flags_ten_consecutive_identical_readings_as_frozen(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        $reading = null;
        foreach (range(1, 10) as $i) {
            $reading = HiveWeight::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'weight_kg' => 42.0,
                'suspect' => false,
                'recorded_at' => now()->subMinutes(10 - $i),
                'created_at' => now(),
            ]);
        }

        $anomaly = (new StuckSensorRuleEvaluator())->evaluate($reading, 'weight');

        $this->assertNotNull($anomaly);
        $this->assertEquals('frozen_sensor', $anomaly->anomaly_type);
    }

    #[Test]
    public function it_returns_null_when_fewer_than_the_threshold_count_exist(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        $reading = null;
        foreach (range(1, 5) as $i) {
            $reading = HiveWeight::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'weight_kg' => 42.0,
                'suspect' => false,
                'recorded_at' => now()->subMinutes(5 - $i),
                'created_at' => now(),
            ]);
        }

        $anomaly = (new StuckSensorRuleEvaluator())->evaluate($reading, 'weight');

        $this->assertNull($anomaly);
    }
}

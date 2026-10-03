<?php

namespace Tests\Unit\Anomaly\RulesEngine;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\RollingStatsService;
use App\Services\Anomaly\RulesEngine\ZScoreRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ZScoreRuleEvaluatorTest extends TestCase
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

    private function weight(IotDevice $device, float $kg, int $minutesAgo): HiveWeight
    {
        return HiveWeight::create([
            'hive_id' => $device->hive_id,
            'device_id' => $device->id,
            'weight_kg' => $kg,
            'suspect' => false,
            'recorded_at' => now()->subMinutes($minutesAgo),
            'created_at' => now(),
        ]);
    }

    private function evaluator(): ZScoreRuleEvaluator
    {
        return new ZScoreRuleEvaluator(app(RollingStatsService::class));
    }

    #[Test]
    public function it_does_not_evaluate_before_the_warm_up_sample_count_is_reached(): void
    {
        $device = $this->makeDevice();

        // Only 5 samples — below the 10-sample warm-up floor.
        foreach ([49, 50, 51, 50, 49] as $i => $kg) {
            $this->weight($device, $kg, 60 - $i);
        }

        $this->assertNull($this->evaluator()->evaluate($this->weight($device, 200.0, 0), 'weight'));
    }

    #[Test]
    public function it_flags_a_reading_far_outside_the_rolling_mean(): void
    {
        $device = $this->makeDevice();

        // Stable baseline around 50 kg, low variance.
        foreach ([49, 50, 51, 50, 49, 51, 50, 49, 50, 51] as $i => $kg) {
            $this->weight($device, $kg, 60 - $i);
        }

        $anomaly = $this->evaluator()->evaluate($this->weight($device, 100.0, 0), 'weight');

        $this->assertNotNull($anomaly);
        $this->assertEquals('statistical_deviation', $anomaly->anomaly_type);
        $this->assertEqualsWithDelta(50.0, $anomaly->record_value['mean'], 0.01);
    }

    #[Test]
    public function a_reading_within_three_standard_deviations_is_not_flagged(): void
    {
        $device = $this->makeDevice();

        foreach ([49, 50, 51, 50, 49, 51, 50, 49, 50, 51] as $i => $kg) {
            $this->weight($device, $kg, 60 - $i);
        }

        $this->assertNull($this->evaluator()->evaluate($this->weight($device, 51.5, 0), 'weight'));
    }

    #[Test]
    public function readings_from_more_than_24_hours_ago_do_not_count_toward_the_warm_up(): void
    {
        $device = $this->makeDevice();

        foreach ([49, 50, 51, 50, 49, 51, 50, 49, 50, 51] as $i => $kg) {
            $this->weight($device, $kg, 25 * 60 + $i);
        }

        $this->assertNull($this->evaluator()->evaluate($this->weight($device, 100.0, 0), 'weight'));
    }
}

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
    public function it_does_not_evaluate_before_the_warm_up_sample_count_is_reached(): void
    {
        [$hive, $device] = $this->makeReadingContext();
        $rollingStats = app(RollingStatsService::class);

        // Only 5 samples — below the 10-sample warm-up floor.
        foreach ([50, 50, 50, 50, 50] as $value) {
            $rollingStats->updateAndGet($hive->id, 'weight', null, $value);
        }

        $reading = HiveWeight::create([
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'weight_kg' => 200.0, // would be a huge outlier if evaluated
            'suspect' => false,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $anomaly = (new ZScoreRuleEvaluator($rollingStats))->evaluate($reading, 'weight');

        $this->assertNull($anomaly);
    }

    #[Test]
    public function it_flags_a_reading_far_outside_the_rolling_mean(): void
    {
        [$hive, $device] = $this->makeReadingContext();
        $rollingStats = app(RollingStatsService::class);

        // Stable baseline around 50kg, low variance.
        foreach ([49, 50, 51, 50, 49, 51, 50, 49, 50, 51] as $value) {
            $rollingStats->updateAndGet($hive->id, 'weight', null, $value);
        }

        $reading = HiveWeight::create([
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'weight_kg' => 100.0,
            'suspect' => false,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $anomaly = (new ZScoreRuleEvaluator($rollingStats))->evaluate($reading, 'weight');

        $this->assertNotNull($anomaly);
        $this->assertEquals('statistical_deviation', $anomaly->anomaly_type);
    }
}

<?php

namespace Tests\Unit\Anomaly\RulesEngine;

use App\Models\AlertThreshold;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveTemperature;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\RulesEngine\ThresholdRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThresholdRuleEvaluatorTest extends TestCase
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
    public function it_returns_null_for_an_in_range_reading(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        $reading = HiveTemperature::create([
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'brood_section' => 35.0,
            'suspect' => false,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $anomaly = (new ThresholdRuleEvaluator())->evaluate($reading, 'temperature');

        $this->assertNull($anomaly);
    }

    #[Test]
    public function it_flags_an_out_of_range_reading_with_static_threshold_breach(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        $reading = HiveTemperature::create([
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'brood_section' => 75.0,
            'suspect' => false,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $anomaly = (new ThresholdRuleEvaluator())->evaluate($reading, 'temperature');

        $this->assertNotNull($anomaly);
        $this->assertEquals('static_threshold_breach', $anomaly->anomaly_type);
        $this->assertEquals(['brood_section' => 75.0], $anomaly->record_value);
    }

    #[Test]
    public function per_hive_threshold_override_takes_precedence_over_the_global_default(): void
    {
        [$hive, $device] = $this->makeReadingContext();

        AlertThreshold::create(['key' => 'weight_max_kg', 'hive_id' => $hive->id, 'value' => '30']);

        $reading = HiveWeight::create([
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'weight_kg' => 35.0, // within the seeded global default (120) but above this hive's override (30)
            'suspect' => false,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $anomaly = (new ThresholdRuleEvaluator())->evaluate($reading, 'weight');

        $this->assertNotNull($anomaly);
        $this->assertEquals('static_threshold_breach', $anomaly->anomaly_type);
    }
}

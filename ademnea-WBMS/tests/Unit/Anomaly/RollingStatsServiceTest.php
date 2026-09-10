<?php

namespace Tests\Unit\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Services\Anomaly\RollingStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RollingStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeHive(): Hive
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);

        return Hive::factory()->create(['apiary_id' => $apiary->id]);
    }

    #[Test]
    public function it_converges_to_the_known_mean_and_variance_for_a_fixed_sequence(): void
    {
        $hive = $this->makeHive();
        $service = app(RollingStatsService::class);

        // Values: 2, 4, 4, 4, 5, 5, 7, 9 — textbook Welford example.
        // Population mean = 5, population variance = 4.
        foreach ([2, 4, 4, 4, 5, 5, 7, 9] as $value) {
            $stats = $service->updateAndGet($hive->id, 'temperature', 'brood_section', $value);
        }

        $this->assertEquals(5.0, round($stats->mean, 6));
        $this->assertEquals(4.0, round($stats->variance / $stats->sample_count, 6));
        $this->assertEquals(8, $stats->sample_count);
    }

    #[Test]
    public function it_resets_the_window_after_24_hours(): void
    {
        $hive = $this->makeHive();
        $service = app(RollingStatsService::class);

        $service->updateAndGet($hive->id, 'temperature', 'brood_section', 100.0);

        $stats = \App\Models\HiveRollingStat::where('hive_id', $hive->id)
            ->where('sensor_type', 'temperature')
            ->where('channel', 'brood_section')
            ->first();
        $stats->update(['window_start' => now()->subHours(25)]);

        $updated = $service->updateAndGet($hive->id, 'temperature', 'brood_section', 20.0);

        $this->assertEquals(20.0, $updated->mean);
        $this->assertEquals(0.0, $updated->variance);
        $this->assertEquals(1, $updated->sample_count);
    }

    #[Test]
    public function it_invalidates_the_cache_on_every_write(): void
    {
        $hive = $this->makeHive();
        $service = app(RollingStatsService::class);

        $service->updateAndGet($hive->id, 'weight', null, 10.0);
        $first = $service->currentStats($hive->id, 'weight', null);

        $service->updateAndGet($hive->id, 'weight', null, 20.0);
        $second = $service->currentStats($hive->id, 'weight', null);

        $this->assertEquals(10.0, $first->mean);
        $this->assertEquals(15.0, $second->mean);
    }
}

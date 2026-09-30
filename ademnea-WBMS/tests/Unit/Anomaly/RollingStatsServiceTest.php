<?php

namespace Tests\Unit\Anomaly;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveRollingStat;
use App\Models\HiveTemperature;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\Anomaly\RollingStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RollingStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): IotDevice
    {
        $farmer = Farmer::factory()->create();
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);

        return IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);
    }

    private function brood(IotDevice $device, float $value, int $minutesAgo, bool $suspect = false): HiveTemperature
    {
        return HiveTemperature::create([
            'hive_id' => $device->hive_id,
            'device_id' => $device->id,
            'brood_section' => $value,
            'suspect' => $suspect,
            'recorded_at' => now()->subMinutes($minutesAgo),
            'created_at' => now(),
        ]);
    }

    #[Test]
    public function the_baseline_is_the_mean_and_population_stddev_of_the_24_hours_before_the_reading(): void
    {
        $device = $this->makeDevice();

        // 2, 4, 4, 4, 5, 5, 7, 9: mean 5, population standard deviation 2.
        foreach ([2, 4, 4, 4, 5, 5, 7, 9] as $i => $value) {
            $this->brood($device, $value, 100 - $i);
        }
        $reading = $this->brood($device, 50, 0);

        $baseline = app(RollingStatsService::class)->baselineBefore($reading, 'temperature', 'brood_section');

        $this->assertSame(8, $baseline['count']); // the reading itself is left out
        $this->assertEqualsWithDelta(5.0, $baseline['mean'], 1e-6);
        $this->assertEqualsWithDelta(2.0, $baseline['stddev'], 1e-6);
    }

    #[Test]
    public function the_window_slides_readings_older_than_24_hours_drop_out(): void
    {
        $device = $this->makeDevice();

        $this->brood($device, 100, 25 * 60); // outside the window
        $this->brood($device, 30, 60);
        $reading = $this->brood($device, 31, 0);

        $baseline = app(RollingStatsService::class)->baselineBefore($reading, 'temperature', 'brood_section');

        $this->assertSame(1, $baseline['count']);
        $this->assertEqualsWithDelta(30.0, $baseline['mean'], 1e-6);
    }

    #[Test]
    public function suspect_readings_are_left_out_of_the_baseline(): void
    {
        $device = $this->makeDevice();

        $this->brood($device, 34, 30);
        $this->brood($device, 90, 20, suspect: true);
        $reading = $this->brood($device, 35, 0);

        $baseline = app(RollingStatsService::class)->baselineBefore($reading, 'temperature', 'brood_section');

        $this->assertSame(1, $baseline['count']);
        $this->assertEqualsWithDelta(34.0, $baseline['mean'], 1e-6);
    }

    #[Test]
    public function an_empty_window_has_no_baseline(): void
    {
        $device = $this->makeDevice();
        $reading = $this->brood($device, 35, 0);

        $this->assertNull(app(RollingStatsService::class)->baselineBefore($reading, 'temperature', 'brood_section'));
    }

    #[Test]
    public function the_snapshot_records_the_window_including_the_reading(): void
    {
        $device = $this->makeDevice();

        $this->brood($device, 30, 10);
        $reading = $this->brood($device, 40, 0);

        app(RollingStatsService::class)->recordSnapshot($reading, 'temperature');

        $stats = HiveRollingStat::where('hive_id', $device->hive_id)->where('channel', 'brood_section')->first();
        $this->assertSame(2, $stats->sample_count);
        $this->assertEqualsWithDelta(35.0, $stats->mean, 1e-6);
        $this->assertEqualsWithDelta(25.0, $stats->variance, 1e-6);

        // Zones with no value on the reading get no snapshot row.
        $this->assertDatabaseMissing('hive_rolling_stats', ['hive_id' => $device->hive_id, 'channel' => 'honey_section']);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveStatusHistory;
use App\Models\HiveTemperature;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private IotDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        // Thursday 1 October, 09:00 UTC: noon in Kampala (UTC+3).
        Carbon::setTestNow(Carbon::parse('2026-10-01 09:00:00', 'UTC'));

        $apiary = Apiary::factory()->create(['farmer_id' => Farmer::factory()->create()->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $this->device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function temperature(string $recordedAtUtc, float $brood): void
    {
        HiveTemperature::create([
            'hive_id' => $this->device->hive_id, 'device_id' => $this->device->id, 'brood_section' => $brood,
            'suspect' => false, 'recorded_at' => Carbon::parse($recordedAtUtc, 'UTC'), 'created_at' => now(),
        ]);
    }

    #[Test]
    public function chart_days_run_from_midnight_to_midnight_in_kampala(): void
    {
        $admin = $this->actingAsAdminWithPermission();

        $this->temperature('2026-09-30 20:00:00', 30); // 23:00 on 30 September in Kampala
        $this->temperature('2026-09-30 22:30:00', 40); // 01:30 on 1 October in Kampala
        $this->temperature('2026-09-24 21:30:00', 20); // 00:30 on 25 September: the first day charted
        $this->temperature('2026-09-24 20:30:00', 99); // 23:30 on 24 September: before the window

        HiveStatusHistory::create([
            'hive_id' => $this->device->hive_id, 'previous_status' => 'Active', 'new_status' => 'Under Inspection',
            'changed_by_user_id' => $admin->id, 'transitioned_at' => now(), 'created_at' => Carbon::parse('2026-09-30 21:15:00', 'UTC'),
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('chartData', fn (array $chart) => $chart['labels'] === ['Sep 25', 'Sep 26', 'Sep 27', 'Sep 28', 'Sep 29', 'Sep 30', 'Oct 01']
                && $chart['temperature'] === [20.0, null, null, null, null, 30.0, 40.0]
                && $chart['hive_activity'] === [0, 0, 0, 0, 0, 0, 1]);
    }

    #[Test]
    public function a_day_that_averages_zero_is_charted_not_treated_as_missing(): void
    {
        $this->actingAsAdminWithPermission();

        HiveWeight::create([
            'hive_id' => $this->device->hive_id, 'device_id' => $this->device->id, 'weight_kg' => 0,
            'suspect' => false, 'recorded_at' => now()->subHour(), 'created_at' => now(),
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="chartWeight"', false)
            ->assertDontSee('Weight data not yet available')
            ->assertSee('Temperature data not yet available')
            ->assertViewHas('chartData', fn (array $chart) => $chart['weight'] === [null, null, null, null, null, null, 0.0]);
    }
}

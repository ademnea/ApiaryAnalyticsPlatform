<?php

namespace Tests\Feature\Admin;

use App\Models\AlertThreshold;
use App\Models\Hive;
use App\Services\Anomaly\DetectionLimitService;
use App\Services\Anomaly\RulesEngine\ThresholdRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class DetectionLimitsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    /** A full, valid submission: every limit at its standard value, then $changes. */
    private function limits(array $changes = []): array
    {
        return $changes + collect(app(DetectionLimitService::class)->fields())->map->default->all();
    }

    #[Test]
    public function a_monitoring_user_can_read_the_limits_but_not_change_them(): void
    {
        $this->actingAsAdminWithPermission('view-anomaly-analytics');
        AlertThreshold::create(['key' => 'temp_max_c', 'value' => '55']);

        $this->get(route('admin.anomaly.limits'))
            ->assertOk()
            ->assertSee('Impossible Reading')
            ->assertSee('View only. Changing limits needs the "manage hives" permission.', false)
            ->assertDontSee('Save changes')
            ->assertViewHas('groups', fn (array $groups) => collect($groups)->flatMap->rules->flatMap->fields->firstWhere('key', 'temp_max_c')['value'] === '55');

        $this->put(route('admin.anomaly.limits.update'), ['limits' => $this->limits()])->assertForbidden();
    }

    #[Test]
    public function saved_fleet_limits_apply_to_the_next_reading(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        // Warm the five-minute cache with the standard value.
        $this->assertSame([-10.0, 60.0], ThresholdRuleEvaluator::bounds($hive->id, 'temperature'));

        $this->put(route('admin.anomaly.limits.update'), ['limits' => $this->limits(['temp_max_c' => '52.5'])])
            ->assertRedirect(route('admin.anomaly.limits'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alert_thresholds', ['key' => 'temp_max_c', 'hive_id' => null, 'value' => '52.5']);
        $this->assertSame([-10.0, 52.5], ThresholdRuleEvaluator::bounds($hive->id, 'temperature'));
        $this->assertSame([-10.0, 52.5], ThresholdRuleEvaluator::bounds(null, 'temperature'));
    }

    #[Test]
    public function a_hive_can_have_its_own_limit_and_give_it_up_again(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();
        $other = Hive::factory()->create();

        $this->put(route('admin.anomaly.limits.update'), ['hive_id' => $hive->id, 'limits' => ['weight_max_kg' => '200', 'latest_firmware_version' => '9.9']])
            ->assertRedirect(route('admin.anomaly.limits', ['hive_id' => $hive->id]));

        $this->assertSame(1, AlertThreshold::where('hive_id', $hive->id)->count());
        $this->assertSame([5.0, 200.0], ThresholdRuleEvaluator::bounds($hive->id, 'weight'));
        $this->assertSame([5.0, 120.0], ThresholdRuleEvaluator::bounds($other->id, 'weight'));

        $this->get(route('admin.anomaly.limits'))->assertOk()->assertViewHas('hivesWithOverrides', fn ($hives) => $hives->pluck('id')->all() === [$hive->id]);
        $this->get(route('admin.anomaly.limits', ['hive_id' => $hive->id]))->assertOk()->assertSee('Own limit');

        $this->put(route('admin.anomaly.limits.update'), ['hive_id' => $hive->id, 'limits' => ['weight_max_kg' => '']]);

        $this->assertSame(0, AlertThreshold::where('hive_id', $hive->id)->count());
        $this->assertSame([5.0, 120.0], ThresholdRuleEvaluator::bounds($hive->id, 'weight'));
    }

    #[Test]
    public function limits_that_make_no_sense_are_rejected(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        $this->put(route('admin.anomaly.limits.update'), ['limits' => $this->limits(['temp_min_c' => '70'])])
            ->assertSessionHasErrors('limits.temp_min_c');

        $this->put(route('admin.anomaly.limits.update'), ['limits' => $this->limits(['low_battery_pct' => '150', 'stuck_sensor_reading_count' => '2.5', 'latest_firmware_version' => 'latest'])])
            ->assertSessionHasErrors(['limits.low_battery_pct', 'limits.stuck_sensor_reading_count', 'limits.latest_firmware_version']);

        $this->put(route('admin.anomaly.limits.update'), ['limits' => ['temp_max_c' => '50']])
            ->assertSessionHasErrors('limits.temp_min_c');

        // A hive's own minimum is checked against the fleet maximum it inherits.
        $this->put(route('admin.anomaly.limits.update'), ['hive_id' => $hive->id, 'limits' => ['weight_min_kg' => '130']])
            ->assertSessionHasErrors('limits.weight_min_kg');

        $this->assertSame(0, AlertThreshold::count());
    }
}

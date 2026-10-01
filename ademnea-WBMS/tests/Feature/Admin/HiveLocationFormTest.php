<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Hive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

/**
 * Filling in a hive's latitude and longitude without typing them.
 */
class HiveLocationFormTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function the_register_and_edit_forms_offer_the_location_button(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create(['apiary_id' => Apiary::factory()->active()->create()->id]);

        $this->get(route('admin.hives.create'))->assertOk()->assertSee('Use my location');
        $this->get(route('admin.hives.edit', $hive))->assertOk()->assertSee('Use my location');
    }

    #[Test]
    public function a_location_taken_from_the_device_is_saved_with_its_accuracy(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $apiary = Apiary::factory()->active()->create();

        $this->post(route('admin.hives.store'), [
            'apiary_id' => $apiary->id, 'display_name' => 'Located Hive', 'hive_type' => 'Langstroth',
            'latitude' => '0.3476123', 'longitude' => '32.5825456', 'accuracy_meters' => '7.50',
        ])->assertRedirect();

        $this->assertDatabaseHas('hives', ['display_name' => 'Located Hive', 'latitude' => 0.3476123, 'longitude' => 32.5825456, 'accuracy_meters' => 7.5]);
    }

    #[Test]
    public function editing_a_hive_saves_a_corrected_location(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $apiary = Apiary::factory()->active()->create();
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id, 'latitude' => 1.0, 'longitude' => 30.0, 'accuracy_meters' => 5]);

        // Coordinates typed by hand: no accuracy comes with them, so the old one is cleared.
        $this->put(route('admin.hives.update', $hive), [
            'display_name' => $hive->display_name, 'hive_type' => $hive->hive_type,
            'latitude' => '0.35', 'longitude' => '32.58', 'accuracy_meters' => '',
        ])->assertRedirect(route('admin.hives.show', $hive));

        $hive->refresh();
        $this->assertSame(0.35, (float) $hive->latitude);
        $this->assertSame(32.58, (float) $hive->longitude);
        $this->assertNull($hive->accuracy_meters);

        $this->put(route('admin.hives.update', $hive), [
            'display_name' => $hive->display_name, 'hive_type' => $hive->hive_type, 'latitude' => '120', 'longitude' => '32.58',
        ])->assertSessionHasErrors('latitude');
    }
}

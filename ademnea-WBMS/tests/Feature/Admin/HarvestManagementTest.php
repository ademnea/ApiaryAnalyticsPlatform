<?php

namespace Tests\Feature\Admin;

use App\Models\Hive;
use App\Models\HarvestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class HarvestManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_harvest_index(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');
        HarvestRecord::factory()->count(3)->create();

        $response = $this->get(route('admin.harvests.index'));

        $response->assertOk();
        $response->assertViewHas('harvests');
    }

    #[Test]
    public function admin_can_create_a_harvest(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');
        $hive = Hive::factory()->create();

        $response = $this->post(route('admin.harvests.store'), [
            'hive_id' => $hive->id,
            'harvest_date' => now()->format('Y-m-d'),
            'honey_yield_kg' => 25.5,
            'beeswax_yield_kg' => 3.2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('harvest_records', [
            'hive_id' => $hive->id,
            'honey_yield_kg' => 25.5,
        ]);
    }

    #[Test]
    public function admin_can_view_a_single_harvest(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');
        $harvest = HarvestRecord::factory()->create();

        $response = $this->get(route('admin.harvests.show', $harvest));

        $response->assertOk();
        $response->assertViewHas('harvest', $harvest);
    }

    #[Test]
    public function admin_can_update_a_harvest(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');
        $harvest = HarvestRecord::factory()->create();

        $response = $this->put(route('admin.harvests.update', $harvest), [
            'honey_yield_kg' => 30.0,
            'notes' => 'Updated yield',
        ]);

        $response->assertRedirect(route('admin.harvests.show', $harvest));
        $this->assertDatabaseHas('harvest_records', [
            'id' => $harvest->id,
            'honey_yield_kg' => 30.0,
            'notes' => 'Updated yield',
        ]);
    }

    #[Test]
    public function admin_can_delete_a_harvest(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');
        $harvest = HarvestRecord::factory()->create();

        $response = $this->delete(route('admin.harvests.destroy', $harvest));

        $response->assertRedirect(route('admin.harvests.index'));
        $this->assertSoftDeleted('harvest_records', [
            'id' => $harvest->id,
        ]);
    }

    #[Test]
    public function harvest_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission('manage-harvests');

        $response = $this->post(route('admin.harvests.store'), [
            'notes' => 'Missing required fields',
        ]);

        $response->assertSessionHasErrors('hive_id');
        $response->assertSessionHasErrors('harvest_date');
    }
}

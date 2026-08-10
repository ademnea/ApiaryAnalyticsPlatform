<?php

namespace Tests\Feature\Admin;

use App\Models\Hive;
use App\Models\Inspection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class InspectionManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_inspection_index(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');
        Inspection::factory()->count(3)->create();

        $response = $this->get(route('admin.inspections.index'));

        $response->assertOk();
        $response->assertViewHas('inspections');
    }

    #[Test]
    public function admin_can_create_an_inspection(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');
        $hive = Hive::factory()->create();

        $response = $this->post(route('admin.inspections.store'), [
            'hive_id' => $hive->id,
            'inspected_at' => now()->format('Y-m-d'),
            'strength_rating' => 'Strong',
            'disease_events' => 'None observed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inspections', [
            'hive_id' => $hive->id,
            'strength_rating' => 'Strong',
        ]);
    }

    #[Test]
    public function admin_can_view_a_single_inspection(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');
        $inspection = Inspection::factory()->create();

        $response = $this->get(route('admin.inspections.show', $inspection));

        $response->assertOk();
        $response->assertViewHas('inspection', $inspection);
    }

    #[Test]
    public function admin_can_update_an_inspection(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');
        $inspection = Inspection::factory()->create();

        $response = $this->put(route('admin.inspections.update', $inspection), [
            'strength_rating' => 'Weak',
            'general_notes' => 'Needs feeding',
        ]);

        $response->assertRedirect(route('admin.inspections.show', $inspection));
        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'strength_rating' => 'Weak',
            'general_notes' => 'Needs feeding',
        ]);
    }

    #[Test]
    public function admin_can_delete_an_inspection(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');
        $inspection = Inspection::factory()->create();

        $response = $this->delete(route('admin.inspections.destroy', $inspection));

        $response->assertRedirect(route('admin.inspections.index'));
        $this->assertSoftDeleted('inspections', [
            'id' => $inspection->id,
        ]);
    }

    #[Test]
    public function inspection_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission('manage-inspections');

        $response = $this->post(route('admin.inspections.store'), [
            'strength_rating' => 'Strong',
        ]);

        $response->assertSessionHasErrors('hive_id');
        $response->assertSessionHasErrors('inspected_at');
    }
}

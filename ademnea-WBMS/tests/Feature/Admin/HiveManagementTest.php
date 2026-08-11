<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Hive;
use App\Models\HiveStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class HiveManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_hive_index(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        Apiary::factory()->create();
        Hive::factory()->count(3)->create();

        $response = $this->get(route('admin.hives.index'));

        $response->assertOk();
        $response->assertViewHas('hives');
    }

    #[Test]
    public function admin_can_see_apiary_selection_when_creating_a_hive(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $apiary = Apiary::factory()->active()->create(['name' => 'Main Apiary']);

        $response = $this->get(route('admin.hives.create'));

        $response->assertOk();
        $response->assertSee('Parent apiary');
        $response->assertSee('Main Apiary');
    }

    #[Test]
    public function admin_can_create_a_hive(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $apiary = Apiary::factory()->active()->create();

        $response = $this->post(route('admin.hives.store'), [
            'apiary_id' => $apiary->id,
            'display_name' => 'Test Hive',
            'hive_type' => 'Langstroth',
            'latitude' => 0.3476,
            'longitude' => 32.5825,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hives', [
            'display_name' => 'Test Hive',
            'apiary_id' => $apiary->id,
        ]);
    }

    #[Test]
    public function admin_can_view_a_single_hive(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        $response = $this->get(route('admin.hives.show', $hive));

        $response->assertOk();
        $response->assertViewHas('hive', $hive);
    }

    #[Test]
    public function admin_can_update_a_hive(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        $response = $this->put(route('admin.hives.update', $hive), [
            'display_name' => 'Updated Hive Name',
            'hive_type' => 'TopBar',
        ]);

        $response->assertRedirect(route('admin.hives.show', $hive));
        $this->assertDatabaseHas('hives', [
            'id' => $hive->id,
            'display_name' => 'Updated Hive Name',
        ]);
    }

    #[Test]
    public function admin_can_change_hive_status(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create(['current_status' => 'Active']);

        $response = $this->patch(route('admin.hives.updateStatus', $hive), [
            'status' => 'Queenless',
            'change_notes' => 'Queen not found during inspection',
        ]);

        $response->assertRedirect(route('admin.hives.show', $hive));
        $this->assertDatabaseHas('hives', [
            'id' => $hive->id,
            'current_status' => 'Queenless',
        ]);
        $this->assertDatabaseHas('hive_status_histories', [
            'hive_id' => $hive->id,
            'new_status' => 'Queenless',
            'reason_note' => 'Queen not found during inspection',
        ]);
    }

    #[Test]
    public function hive_status_change_creates_audit_history(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create(['current_status' => 'Active']);

        $this->patch(route('admin.hives.updateStatus', $hive), [
            'status' => 'Absconded',
        ]);

        $history = HiveStatusHistory::where('hive_id', $hive->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('Active', $history->previous_status);
        $this->assertEquals('Absconded', $history->new_status);
    }

    #[Test]
    public function admin_can_delete_a_hive(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        $response = $this->delete(route('admin.hives.destroy', $hive));

        $response->assertRedirect(route('admin.hives.index'));
        $this->assertSoftDeleted('hives', [
            'id' => $hive->id,
        ]);
    }

    #[Test]
    public function hive_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $response = $this->post(route('admin.hives.store'), [
            'display_name' => 'Test Hive',
        ]);

        $response->assertSessionHasErrors('apiary_id');
        $response->assertSessionHasErrors('latitude');
        $response->assertSessionHasErrors('longitude');
    }
}

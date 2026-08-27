<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class ApiaryManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_apiary_index(): void
    {
        $this->actingAsAdminWithPermission();
        Apiary::factory()->count(3)->create();

        $response = $this->get(route('admin.apiaries.index'));

        $response->assertOk();
        $response->assertViewHas('apiaries');
    }

    #[Test]
    public function admin_can_create_an_apiary(): void
    {
        $this->actingAsAdminWithPermission();

        $response = $this->post(route('admin.apiaries.store'), [
            'name' => 'Test Apiary',
            'country' => 'UG',
            'region' => 'Central',
            'status' => 'Active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('apiaries', [
            'name' => 'Test Apiary',
            'country' => 'UG',
            'status' => 'Active',
        ]);
    }

    #[Test]
    public function admin_can_view_a_single_apiary(): void
    {
        $this->actingAsAdminWithPermission();
        $apiary = Apiary::factory()->create();

        $response = $this->get(route('admin.apiaries.show', $apiary));

        $response->assertOk();
        $response->assertViewHas('apiary', $apiary);
    }

    #[Test]
    public function admin_can_update_an_apiary(): void
    {
        $this->actingAsAdminWithPermission();
        $apiary = Apiary::factory()->create();

        $response = $this->put(route('admin.apiaries.update', $apiary), [
            'name' => 'Updated Apiary Name',
            'country' => 'UG',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.apiaries.show', $apiary));
        $this->assertDatabaseHas('apiaries', [
            'id' => $apiary->id,
            'name' => 'Updated Apiary Name',
        ]);
    }

    #[Test]
    public function admin_can_deactivate_an_apiary(): void
    {
        $this->actingAsAdminWithPermission();
        $apiary = Apiary::factory()->active()->create();

        $response = $this->patch(route('admin.apiaries.deactivate', $apiary));

        $response->assertRedirect(route('admin.apiaries.index'));
        $this->assertDatabaseHas('apiaries', [
            'id' => $apiary->id,
            'status' => 'Inactive',
        ]);
    }

    #[Test]
    public function admin_can_delete_an_apiary(): void
    {
        $this->actingAsAdminWithPermission();
        $apiary = Apiary::factory()->create();

        $response = $this->delete(route('admin.apiaries.destroy', $apiary));

        $response->assertRedirect(route('admin.apiaries.index'));
        $this->assertSoftDeleted('apiaries', [
            'id' => $apiary->id,
        ]);
    }

    #[Test]
    public function apiary_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission();

        $response = $this->post(route('admin.apiaries.store'), [
            'country' => 'UG',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function apiary_name_must_be_unique_per_farmer_and_country(): void
    {
        $this->actingAsAdminWithPermission();
        $farmer = Farmer::factory()->create();
        Apiary::factory()->create([
            'name' => 'Duplicate Name',
            'country' => 'UG',
            'farmer_id' => $farmer->id,
        ]);

        $response = $this->post(route('admin.apiaries.store'), [
            'name' => 'Duplicate Name',
            'country' => 'UG',
            'farmer_id' => $farmer->id,
        ]);

        $response->assertSessionHasErrors('name');
    }
}

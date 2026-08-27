<?php

namespace Tests\Feature\Admin;

use App\Models\Farmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class FarmerManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_farmer_index(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        Farmer::factory()->count(3)->create();

        $response = $this->get(route('admin.farmers.index'));

        $response->assertOk();
        $response->assertViewHas('farmers');
    }

    #[Test]
    public function admin_can_create_a_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');

        $response = $this->post(route('admin.farmers.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+256700000000',
            'country' => 'UG',
            'status' => 'Active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('farmers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function admin_can_view_a_single_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create();

        $response = $this->get(route('admin.farmers.show', $farmer));

        $response->assertOk();
        $response->assertViewHas('farmer', $farmer);
    }

    #[Test]
    public function admin_can_update_a_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create();

        $response = $this->put(route('admin.farmers.update', $farmer), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '+256711111111',
        ]);

        $response->assertRedirect(route('admin.farmers.show', $farmer));
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'first_name' => 'Jane',
        ]);
    }

    #[Test]
    public function admin_can_delete_a_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create();

        $response = $this->delete(route('admin.farmers.destroy', $farmer));

        $response->assertRedirect(route('admin.farmers.index'));
        $this->assertSoftDeleted('farmers', [
            'id' => $farmer->id,
        ]);
    }

    #[Test]
    public function admin_can_restore_a_deleted_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create();
        $farmer->delete();

        $response = $this->patch(route('admin.farmers.restore', $farmer));

        $response->assertRedirect(route('admin.farmers.show', $farmer));
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function farmer_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');

        $response = $this->post(route('admin.farmers.store'), [
            'email' => 'invalid',
        ]);

        $response->assertSessionHasErrors('first_name');
        $response->assertSessionHasErrors('last_name');
    }

    #[Test]
    public function farmer_email_must_be_unique(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        Farmer::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->post(route('admin.farmers.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'duplicate@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

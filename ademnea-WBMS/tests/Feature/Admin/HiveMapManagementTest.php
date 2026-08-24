<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Hive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class HiveMapManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function user_without_hive_data_permission_cannot_view_the_hive_map(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.hives.map'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_view_the_hive_map(): void
    {
        $this->actingAsAdminWithPermission('view-hive-data');

        $this->get(route('admin.hives.map'))
            ->assertOk()
            ->assertViewIs('admin.apiary-management.hives.map')
            ->assertSee('hive-map');
    }

    #[Test]
    public function map_data_returns_only_hives_inside_the_requested_bounds(): void
    {
        $this->actingAsAdminWithPermission('view-hive-data');
        $apiary = Apiary::factory()->create(['name' => 'Central Apiary']);
        $inside = Hive::factory()->forApiary($apiary)->create([
            'display_name' => 'Inside Hive',
            'latitude' => 0.35,
            'longitude' => 32.58,
        ]);
        Hive::factory()->forApiary($apiary)->create([
            'display_name' => 'Outside Hive',
            'latitude' => 2.00,
            'longitude' => 34.00,
        ]);

        $response = $this->getJson(route('admin.hives.map-data', [
            'sw_lat' => 0,
            'sw_lng' => 32,
            'ne_lat' => 1,
            'ne_lng' => 33,
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inside->id)
            ->assertJsonPath('data.0.apiary.name', 'Central Apiary');
    }

    #[Test]
    public function map_data_rejects_an_invalid_bounding_box(): void
    {
        $this->actingAsAdminWithPermission('view-hive-data');

        $this->getJson(route('admin.hives.map-data', [
            'sw_lat' => -91,
            'sw_lng' => 32,
            'ne_lat' => 1,
            'ne_lng' => 33,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid bounding box parameters.')
            ->assertJsonStructure(['details' => ['sw_lat']]);
    }
}

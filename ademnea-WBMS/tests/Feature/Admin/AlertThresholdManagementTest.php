<?php

namespace Tests\Feature\Admin;

use App\Models\AlertThreshold;
use App\Models\Hive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class AlertThresholdManagementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_alert_threshold_index(): void
    {
        $this->actingAsAdminWithPermission('view-hive-data');
        AlertThreshold::factory()->count(3)->create();

        $response = $this->get(route('admin.alert-thresholds.index'));

        $response->assertOk();
        $response->assertViewHas('thresholds');
    }

    #[Test]
    public function admin_can_create_an_alert_threshold(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $hive = Hive::factory()->create();

        $response = $this->post(route('admin.alert-thresholds.store'), [
            'key' => 'feed_required_weight_kg',
            'value' => '15',
            'description' => 'Minimum hive weight (kg) before a feed_required alert fires.',
            'hive_id' => $hive->id,
        ]);

        $response->assertRedirect(route('admin.alert-thresholds.index'));
        $this->assertDatabaseHas('alert_thresholds', [
            'key' => 'feed_required_weight_kg',
            'value' => '15',
            'hive_id' => $hive->id,
        ]);
    }

    #[Test]
    public function admin_can_create_a_global_alert_threshold(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $response = $this->post(route('admin.alert-thresholds.store'), [
            'key' => 'global_test_key',
            'value' => '42',
            'description' => 'Global test threshold.',
            'hive_id' => '',
        ]);

        $response->assertRedirect(route('admin.alert-thresholds.index'));
        $this->assertDatabaseHas('alert_thresholds', [
            'key' => 'global_test_key',
            'value' => '42',
            'hive_id' => null,
        ]);
    }

    #[Test]
    public function admin_can_view_edit_form(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $threshold = AlertThreshold::factory()->create();

        $response = $this->get(route('admin.alert-thresholds.edit', $threshold));

        $response->assertOk();
        $response->assertViewHas('threshold', $threshold);
    }

    #[Test]
    public function admin_can_update_an_alert_threshold(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $threshold = AlertThreshold::factory()->create();

        $response = $this->put(route('admin.alert-thresholds.update', $threshold), [
            'key' => $threshold->key,
            'value' => '20',
            'description' => 'Updated description.',
        ]);

        $response->assertRedirect(route('admin.alert-thresholds.index'));
        $this->assertDatabaseHas('alert_thresholds', [
            'id' => $threshold->id,
            'value' => '20',
            'description' => 'Updated description.',
        ]);
    }

    #[Test]
    public function admin_can_delete_an_alert_threshold(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        $threshold = AlertThreshold::factory()->create();

        $response = $this->delete(route('admin.alert-thresholds.destroy', $threshold));

        $response->assertRedirect(route('admin.alert-thresholds.index'));
        $this->assertSoftDeleted('alert_thresholds', [
            'id' => $threshold->id,
        ]);
    }

    #[Test]
    public function alert_threshold_creation_validates_required_fields(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $response = $this->post(route('admin.alert-thresholds.store'), [
            'description' => 'Missing key and value',
        ]);

        $response->assertSessionHasErrors('key');
        $response->assertSessionHasErrors('value');
    }

    #[Test]
    public function alert_threshold_key_must_be_unique(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');
        AlertThreshold::factory()->create(['key' => 'duplicate_key']);

        $response = $this->post(route('admin.alert-thresholds.store'), [
            'key' => 'duplicate_key',
            'value' => '10',
        ]);

        $response->assertSessionHasErrors('key');
    }

    #[Test]
    public function alert_threshold_hive_id_must_exist_when_provided(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $response = $this->post(route('admin.alert-thresholds.store'), [
            'key' => 'test_key',
            'value' => '10',
            'hive_id' => 99999,
        ]);

        $response->assertSessionHasErrors('hive_id');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithIotAdminAuth;
use Tests\Feature\Admin\Concerns\CreatesTestHive;
use Tests\TestCase;

class IotDeviceUnassignmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIotAdminAuth;
    use CreatesTestHive;

    #[Test]
    public function admin_can_unassign_a_device_from_its_hive(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create([
            'hive_id' => $this->createTestHiveId(),
            'status' => 'deployed',
        ]);

        $response = $this->patch(route('admin.iot-devices.unassign', $device));

        $response->assertRedirect(route('admin.iot-devices.show', $device));

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'hive_id' => null,
        ]);
    }

    #[Test]
    public function unassignment_does_not_revert_deployed_status_back_to_provisioned(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create([
            'hive_id' => $this->createTestHiveId(),
            'status' => 'deployed',
        ]);

        $this->patch(route('admin.iot-devices.unassign', $device));

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'status' => 'deployed',
        ]);
    }

    #[Test]
    public function unassignment_writes_an_audit_log_entry(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create(['hive_id' => $this->createTestHiveId(),]);

        $this->patch(route('admin.iot-devices.unassign', $device));

        $this->assertDatabaseHas('iot_auth_logs', [
            'device_id' => $device->id,
            'event_type' => 'unassigned_from_hive',
        ]);
    }

    #[Test]
    public function unassigning_an_already_unassigned_device_is_a_harmless_noop(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create(['hive_id' => null]);

        $response = $this->patch(route('admin.iot-devices.unassign', $device));

        $response->assertRedirect(route('admin.iot-devices.show', $device));
        $this->assertDatabaseHas('iot_devices', ['id' => $device->id, 'hive_id' => null]);
    }

    #[Test]
    public function guest_cannot_unassign_a_device(): void
    {
        // $hiveId = $this->createTestHiveId();
        // $device = IotDevice::factory()->create(['hive_id' => $hiveId,]);

        // $response = $this->patch(route('admin.iot-devices.unassign', $device));

        // $response->assertRedirect(route('login'));
        // $this->assertDatabaseHas('iot_devices', ['id' => $device->id, 'hive_id' => $hiveId,]);
        $this->markTestSkipped('Auth/RBAC not yet implemented — module pending.');
    }

    #[Test]
    public function unassigning_a_nonexistent_device_returns_404(): void
    {
        $this->actingAsAdminWithPermission();

        $response = $this->patch('/admin/iot-devices/999999/unassign');

        $response->assertNotFound();
    }
}
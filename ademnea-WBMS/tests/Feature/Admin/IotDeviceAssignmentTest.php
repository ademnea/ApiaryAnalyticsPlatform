<?php

namespace Tests\Feature\Admin;

use App\Models\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithIotAdminAuth;
use Tests\Feature\Admin\Concerns\CreatesTestHive;
use Tests\TestCase;

class IotDeviceAssignmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIotAdminAuth;
    use CreatesTestHive;

    #[Test]
    public function admin_can_assign_an_unassigned_device_to_a_hive(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create([
            'hive_id' => null,
            'status' => 'provisioned',
        ]);
        $hiveId = $this->createTestHiveId();
        $response = $this->post(route('admin.iot-devices.assign.store', $device), [
            'hive_id' => $hiveId,
        ]);

        $response->assertRedirect(route('admin.iot-devices.show', $device));

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'hive_id' => $hiveId,
        ]);
    }

    #[Test]
    public function assigning_a_provisioned_device_automatically_bumps_status_to_deployed(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create([
            'hive_id' => null,
            'status' => 'provisioned',
        ]);

        $this->post(route('admin.iot-devices.assign.store', $device), [
            'hive_id' => $this->createTestHiveId(),
        ]);

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'status' => 'deployed',
        ]);
    }

    #[Test]
    public function assigning_a_device_that_is_not_provisioned_does_not_change_its_existing_status(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create([
            'hive_id' => null,
            'status' => 'offline',
        ]);

        $this->post(route('admin.iot-devices.assign.store', $device), [
          'hive_id' => $this->createTestHiveId(),
        ]);

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'status' => 'offline',
        ]);
    }

    #[Test]
    public function assignment_writes_an_audit_log_entry(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create(['hive_id' => null]);

        $this->post(route('admin.iot-devices.assign.store', $device), [
            'hive_id' => $this->createTestHiveId()
        ]);

        $this->assertDatabaseHas('iot_auth_logs', [
            'device_id' => $device->id,
            'event_type' => 'assigned_to_hive',
        ]);
    }

    #[Test]
    public function admin_cannot_open_the_assignment_form_for_a_device_that_already_has_a_hive(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create(['hive_id' => $this->createTestHiveId()]);

        $response = $this->get(route('admin.iot-devices.assign.form', $device));

        $response->assertForbidden();
    }

    #[Test]
    public function assignment_requires_a_hive_id(): void
    {
        $this->actingAsAdminWithPermission();
        $device = IotDevice::factory()->create(['hive_id' => null]);

        $response = $this->post(route('admin.iot-devices.assign.store', $device), []);

        $response->assertSessionHasErrors('hive_id');
        $this->assertDatabaseHas('iot_devices', [
            'id' => $device->id,
            'hive_id' => null,
        ]);
    }

    #[Test]
    public function guest_cannot_assign_a_device_to_a_hive(): void
    {
        // $device = IotDevice::factory()->create(['hive_id' => null]);

        // $response = $this->post(route('admin.iot-devices.assign.store', $device), [
        //     'hive_id' => $this->createTestHiveId()
        // ]);

        // $response->assertRedirect(route('login'));
        // $this->assertDatabaseHas('iot_devices', ['id' => $device->id, 'hive_id' => null]);

        $this->markTestSkipped('Auth/RBAC not yet implemented — module pending.');
    }
}
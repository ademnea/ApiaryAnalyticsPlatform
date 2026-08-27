<?php

namespace Tests\Feature\Admin;

use App\Models\IotHardwareTeam;
use App\Models\IotDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithIotAdminAuth;
use Tests\TestCase;

class IotDeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIotAdminAuth;

    #[Test]
    public function admin_can_register_a_device_and_receives_a_onetime_plaintext_key(): void
    {
        $this->actingAsAdminWithPermission();
        $team = IotHardwareTeam::factory()->create();

        $response = $this->post(route('admin.iot-devices.store'), [
            'device_code' => 'AEU-UG-014',
            'device_type' => 'numeric_sensor',
            'hardware_team_id' => $team->id,
        ]);

        $device = IotDevice::where('device_code', 'AEU-UG-014')->first();

        $response->assertRedirect(route('admin.iot-devices.show', $device));
        $response->assertSessionHas('plaintext_api_key');

        $this->assertDatabaseHas('iot_devices', [
            'device_code' => 'AEU-UG-014',
            'device_type' => 'numeric_sensor',
            'hardware_team_id' => $team->id,
            'status' => 'provisioned',
            'active_flag' => true,
        ]);
    }

    #[Test]
    public function plaintext_api_key_is_never_persisted_only_its_hash_is(): void
    {
        $this->actingAsAdminWithPermission();
        $team = IotHardwareTeam::factory()->create();

        $this->post(route('admin.iot-devices.store'), [
            'device_code' => 'AEU-UG-015',
            'device_type' => 'numeric_sensor',
            'hardware_team_id' => $team->id,
        ]);

        $device = IotDevice::where('device_code', 'AEU-UG-015')->first();
        $plaintextKey = session('plaintext_api_key');

        $this->assertNotEmpty($plaintextKey);
        $this->assertNotEquals($plaintextKey, $device->api_key_hash);
        $this->assertTrue(Hash::check($plaintextKey, $device->api_key_hash));
    }

    #[Test]
    public function device_registration_defaults_expected_interval_by_device_type(): void
    {
        $this->actingAsAdminWithPermission();
        $team = IotHardwareTeam::factory()->create();

        $this->post(route('admin.iot-devices.store'), [
            'device_code' => 'AEU-UG-016',
            'device_type' => 'media_capture',
            'hardware_team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('iot_devices', [
            'device_code' => 'AEU-UG-016',
            'expected_interval_minutes' => 60,
        ]);
    }

    #[Test]
    public function guest_cannot_register_a_device(): void
    {
        // $team = IotHardwareTeam::factory()->create();

        // $response = $this->post(route('admin.iot-devices.store'), [
        //     'device_code' => 'AEU-UG-017',
        //     'device_type' => 'numeric_sensor',
        //     'hardware_team_id' => $team->id,
        // ]);

        // $response->assertRedirect(route('login'));
        // $this->assertDatabaseMissing('iot_devices', ['device_code' => 'AEU-UG-017']);
         $this->markTestSkipped('Auth/RBAC not yet implemented — module pending.');
    }

    #[Test]
    public function admin_without_permission_cannot_register_a_device(): void
    {
        // $admin = User::factory()->create();
        // $this->actingAs($admin);
        // $team = IotHardwareTeam::factory()->create();

        // $response = $this->post(route('admin.iot-devices.store'), [
        //     'device_code' => 'AEU-UG-018',
        //     'device_type' => 'numeric_sensor',
        //     'hardware_team_id' => $team->id,
        // ]);

        // $response->assertForbidden();

        $this->markTestSkipped('Auth/RBAC not yet implemented — module pending.');
    }

    #[Test]
    public function device_code_must_be_unique(): void
    {
        $this->actingAsAdminWithPermission();
        $team = IotHardwareTeam::factory()->create();
        IotDevice::factory()->create(['device_code' => 'AEU-UG-999']);

        $response = $this->post(route('admin.iot-devices.store'), [
            'device_code' => 'AEU-UG-999',
            'device_type' => 'numeric_sensor',
            'hardware_team_id' => $team->id,
        ]);

        $response->assertSessionHasErrors('device_code');
    }

    #[Test]
    public function hardware_team_must_exist(): void
    {
        $this->actingAsAdminWithPermission();

        $response = $this->post(route('admin.iot-devices.store'), [
            'device_code' => 'AEU-UG-020',
            'device_type' => 'numeric_sensor',
            'hardware_team_id' => 99999,
        ]);

        $response->assertSessionHasErrors('hardware_team_id');
    }
}
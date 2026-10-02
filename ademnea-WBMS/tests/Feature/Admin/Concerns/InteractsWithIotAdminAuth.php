<?php

namespace Tests\Feature\Admin\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Permission;

trait InteractsWithIotAdminAuth
{
    /**
     * Log in as a user holding the permission the IoT admin routes require.
     */
    protected function actingAsAdminWithPermission(): User
    {
        Permission::findOrCreate('manage-iot-devices', 'web');

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage-iot-devices');
        $this->actingAs($admin);

        return $admin;
    }
}

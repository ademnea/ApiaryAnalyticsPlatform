<?php

namespace Tests\Feature\Admin\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Permission;

trait InteractsWithApiaryAdminAuth
{
    protected function actingAsAdminWithPermission(string $permission = 'manage-apiaries'): User
    {
        Permission::findOrCreate($permission, 'web');

        $admin = User::factory()->create();
        $admin->givePermissionTo($permission);
        $this->actingAs($admin);

        return $admin;
    }
}

<?php

namespace Tests\Feature\Api\Farmer;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The role gate on the protected routes.
 *
 * This is the test that would have caught the orphaned seeder which created
 * the farmer role under a 'sanctum' guard that config/auth.php never defines:
 * roles created there would never match a token-authenticated User.
 */
class AccessControlTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_farmer_role_granted_under_the_web_guard_satisfies_a_sanctum_request(): void
    {
        [$user] = $this->makeFarmer(withRole: true);

        $this->authed($user)->getJson('/api/v1/farmer/profile')->assertOk();
    }

    public function test_user_without_the_farmer_role_is_forbidden(): void
    {
        [$user] = $this->makeFarmer(withRole: false);

        $this->authed($user)->getJson('/api/v1/farmer/profile')->assertStatus(403);
    }

    public function test_farmer_write_role_also_grants_access(): void
    {
        [$user] = $this->makeFarmer(withRole: false);
        $user->assignRole('farmer-write');

        $this->authed($user)->getJson('/api/v1/farmer/profile')->assertOk();
    }

    public function test_protected_routes_reject_an_unauthenticated_caller(): void
    {
        $this->getJson('/api/v1/farmer/profile')->assertStatus(401);
    }

    /**
     * UC-FAPI-03: signing out is token self-management, so it is intentionally
     * not behind the role gate — otherwise a session whose role was removed
     * could never be cleaned up.
     */
    public function test_logout_does_not_require_the_farmer_role(): void
    {
        [$user] = $this->makeFarmer(withRole: false);

        $this->authed($user)->postJson('/api/v1/farmer/logout')->assertOk();
    }
}

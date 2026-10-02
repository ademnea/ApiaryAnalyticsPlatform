<?php

namespace Tests\Feature\Admin;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * UC-FAPI-01, admin approval.
 *
 * FarmerPendingApprovalTest already covers the farmers.profile_status
 * transitions for registry-only rows. This covers the case that actually
 * unblocks the mobile app: a self-registered farmer with a linked User,
 * where approval must also activate the login account. Before this existed,
 * approving flipped only profile_status and the farmer still could not sign in.
 */
class FarmerApprovalLinksUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('farmer', 'web');
        Role::findOrCreate('farmer-write', 'web');
        // The approve/reject routes sit in the permission:manage-farmers group.
        Permission::findOrCreate('manage-farmers', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);
        $admin->givePermissionTo('manage-farmers');

        return $admin;
    }

    /**
     * @return array{0: User, 1: Farmer}
     */
    private function pendingSelfRegisteredFarmer(): array
    {
        $user = User::create([
            'name'     => 'Jane Farmer',
            'email'    => 'jane@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'farmer',
            'status'   => 'pending',
        ]);

        $farmer = Farmer::create([
            'user_id'        => $user->id,
            'email'          => 'jane@example.com',
            'first_name'     => 'Jane',
            'last_name'      => 'Farmer',
            'status'         => 'Inactive',
            'profile_status' => 'pending',
        ]);

        return [$user, $farmer];
    }

    public function test_approving_activates_the_linked_login_account(): void
    {
        [$user, $farmer] = $this->pendingSelfRegisteredFarmer();

        $this->actingAs($this->admin())
            ->post(route('admin.farmers.approve', $farmer))
            ->assertRedirect(route('admin.farmers.pending'));

        $this->assertDatabaseHas('farmers', [
            'id'             => $farmer->id,
            'profile_status' => 'active',
            'status'         => 'Active',
        ]);

        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'active',
        ]);

        $this->assertTrue($user->fresh()->hasRole('farmer'));
    }

    public function test_approved_farmer_can_then_log_in(): void
    {
        [, $farmer] = $this->pendingSelfRegisteredFarmer();

        $this->postJson('/api/v1/farmer/login', [
            'email'    => 'jane@example.com',
            'password' => 'password123',
        ])->assertStatus(403);

        $this->actingAs($this->admin())->post(route('admin.farmers.approve', $farmer));

        $this->postJson('/api/v1/farmer/login', [
            'email'    => 'jane@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'expires_at', 'farmer' => ['id', 'name', 'email', 'role']]);
    }

    public function test_elevated_approval_grants_both_roles(): void
    {
        [$user, $farmer] = $this->pendingSelfRegisteredFarmer();

        $this->actingAs($this->admin())
            ->post(route('admin.farmers.approve', $farmer), ['role' => 'farmer-write']);

        $user->refresh();

        // farmer-write is additive, so the base role must survive.
        $this->assertTrue($user->hasRole('farmer'));
        $this->assertTrue($user->hasRole('farmer-write'));
    }

    public function test_rejecting_blocks_login_and_revokes_existing_tokens(): void
    {
        [$user, $farmer] = $this->pendingSelfRegisteredFarmer();

        $user->createToken('stale-session');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($this->admin())->post(route('admin.farmers.reject', $farmer));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('farmers', ['id' => $farmer->id, 'profile_status' => 'incomplete']);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/farmer/login', [
            'email'    => 'jane@example.com',
            'password' => 'password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your account registration was not approved. Please contact the project team.');
    }

    /**
     * A registry-only farmer has no User. Approval must record the decision
     * rather than blowing up on a null relation.
     */
    public function test_approving_a_registry_only_farmer_does_not_error(): void
    {
        $farmer = Farmer::factory()->create(['profile_status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.farmers.approve', $farmer))
            ->assertRedirect(route('admin.farmers.pending'));

        $this->assertDatabaseHas('farmers', [
            'id'             => $farmer->id,
            'profile_status' => 'active',
        ]);
    }
}

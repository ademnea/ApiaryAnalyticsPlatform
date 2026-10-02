<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Api\Farmer\Concerns\CreatesFarmerAccounts;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use CreatesFarmerAccounts;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_farmer_can_register(): void
    {
        $response = $this->postJson('/api/v1/farmer/register', [
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'telephone' => '256700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Registration submitted. Awaiting admin approval.']);

        $user = User::where('email', 'farmer@example.com')->firstOrFail();
        $this->assertSame('farmer', $user->role);
        $this->assertSame('pending', $user->status);
        $this->assertTrue($user->hasRole('farmer'));

        $this->assertDatabaseHas('farmers', [
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Farmer',
            'telephone' => '256700000000',
            'profile_status' => 'pending',
        ]);
    }

    public function test_register_requires_valid_email(): void
    {
        $this->postJson('/api/v1/farmer/register', [
            'name' => 'Test Farmer',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_unique_email(): void
    {
        $this->createFarmerAccount(email: 'existing@example.com');

        $this->postJson('/api/v1/farmer/register', [
            'name' => 'Test Farmer',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_farmer_can_login(): void
    {
        $account = $this->createFarmerAccount();

        $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'password123',
        ])->assertStatus(200)
            ->assertJsonStructure(['token', 'expires_at', 'farmer' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('farmer.id', $account['farmer']->id);
    }

    public function test_login_rejects_pending_account(): void
    {
        $this->createFarmerAccount(status: 'pending');

        $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'password123',
        ])->assertStatus(403)
            ->assertJson(['message' => 'Your account is awaiting administrator approval.']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createFarmerAccount();

        $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_rejects_non_farmer_accounts(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/farmer/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_farmer_can_logout(): void
    {
        $account = $this->createFarmerAccount();

        $this->asFarmer($account)->postJson('/api/v1/farmer/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/farmer/logout')->assertStatus(401);
    }

    public function test_registration_then_admin_approval_lets_farmer_log_in(): void
    {
        $this->postJson('/api/v1/farmer/register', [
            'name' => 'New Farmer',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $login = fn () => $this->postJson('/api/v1/farmer/login', ['email' => 'new@example.com', 'password' => 'password123']);
        $login()->assertStatus(403);

        // The new farmer shows up in the admin pending list and gets approved there.
        Permission::findOrCreate('manage-farmers', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage-farmers');
        $farmer = Farmer::where('email', 'new@example.com')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.farmers.pending'))->assertSee('New Farmer');
        $this->actingAs($admin)->post(route('admin.farmers.approve', $farmer))->assertRedirect();

        $login()->assertStatus(200)->assertJsonStructure(['token']);
    }

    public function test_registered_farmer_cannot_open_the_admin_dashboard(): void
    {
        $account = $this->createFarmerAccount();

        $this->actingAs($account['user'])->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        $this->createFarmerAccount();

        $known = $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com']);
        $unknown = $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'nobody@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json(), $unknown->json());
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'farmer@example.com']);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $this->createFarmerAccount();
        DB::table('password_reset_tokens')->insert([
            'email' => 'farmer@example.com',
            'token' => Hash::make('reset-token'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => 'farmer@example.com',
            'token' => 'reset-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'new-password-123',
        ])->assertOk();
    }

    public function test_farmer_can_view_and_update_profile_and_register_device_token(): void
    {
        $account = $this->createFarmerAccount();

        $this->asFarmer($account)->getJson('/api/v1/farmer/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $account['farmer']->id)
            ->assertJsonPath('data.email', 'farmer@example.com');

        $this->asFarmer($account)->putJson('/api/v1/farmer/profile', ['address' => 'Mukono'])
            ->assertOk()
            ->assertJsonPath('data.address', 'Mukono');

        $this->asFarmer($account)->postJson('/api/v1/farmer/device-token', ['device_token' => 'fcm-abc'])
            ->assertOk();
        $this->assertSame('fcm-abc', $account['farmer']->fresh()->fcm_token);
    }
}

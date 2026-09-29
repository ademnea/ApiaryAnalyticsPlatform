<?php

namespace Tests\Feature\Api\Farmer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * UC-FAPI-05.
 */
class ProfileTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_profile_returns_every_field_the_spec_lists(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->getJson('/api/v1/farmer/profile')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['first_name', 'last_name', 'gender', 'email', 'telephone', 'address', 'role'],
            ])
            ->assertJsonPath('data.email', 'farmer@example.com')
            ->assertJsonPath('data.role', 'farmer');
    }

    public function test_farmer_can_update_own_profile(): void
    {
        [$user, $farmer] = $this->makeFarmer();

        $this->authed($user)
            ->putJson('/api/v1/farmer/profile', [
                'telephone' => '+256711223344',
                'address'   => 'Plot 7, Gulu',
                'gender'    => 'Female',
            ])
            ->assertOk()
            ->assertJsonPath('data.address', 'Plot 7, Gulu');

        $this->assertDatabaseHas('farmers', [
            'id'        => $farmer->id,
            'telephone' => '+256711223344',
            'address'   => 'Plot 7, Gulu',
        ]);
    }

    public function test_profile_update_cannot_change_email_or_role(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->putJson('/api/v1/farmer/profile', [
                'email'   => 'attacker@example.com',
                'role'    => 'admin',
                'address' => 'Somewhere',
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('farmer@example.com', $user->email);
        $this->assertSame('farmer', $user->role);
    }

    public function test_malformed_telephone_is_rejected(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->putJson('/api/v1/farmer/profile', ['telephone' => 'not-a-number'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['telephone']);
    }

    public function test_password_change_takes_effect_on_next_login(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->putJson('/api/v1/farmer/profile', [
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        $this->postJson('/api/v1/farmer/login', [
            'email'    => 'farmer@example.com',
            'password' => 'new-password-123',
        ])->assertOk();
    }
}

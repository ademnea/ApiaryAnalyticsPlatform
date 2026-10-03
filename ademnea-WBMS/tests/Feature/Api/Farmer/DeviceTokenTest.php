<?php

namespace Tests\Feature\Api\Farmer;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * UC-FAPI-14.
 */
class DeviceTokenTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_device_token_is_stored_on_the_farmer_not_the_user(): void
    {
        [$user, $farmer] = $this->makeFarmer();

        $this->authed($user)
            ->postJson('/api/v1/farmer/device-token', ['device_token' => 'fcm-abc-123'])
            ->assertOk()
            ->assertJsonPath('message', 'Device token registered successfully.');

        $this->assertDatabaseHas('farmers', [
            'id'        => $farmer->id,
            'fcm_token' => 'fcm-abc-123',
        ]);

        // The users table also has an fcm_token column, which is where this
        // used to be written. It must stay untouched.
        $this->assertNull($user->fresh()->fcm_token);
    }

    public function test_device_token_is_never_echoed_back(): void
    {
        [$user] = $this->makeFarmer();

        $response = $this->authed($user)
            ->postJson('/api/v1/farmer/device-token', ['device_token' => 'fcm-secret-value']);

        $response->assertOk();
        $this->assertStringNotContainsString('fcm-secret-value', $response->getContent());
    }

    /**
     * The rule list previously contained 'not_empty', which is not a Laravel
     * rule — every request raised BadMethodCallException, a 500 rather than
     * a validation error.
     */
    public function test_missing_device_token_is_a_validation_error_not_a_server_error(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->postJson('/api/v1/farmer/device-token', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['device_token']);
    }

    public function test_empty_device_token_is_rejected(): void
    {
        [$user] = $this->makeFarmer();

        $this->authed($user)
            ->postJson('/api/v1/farmer/device-token', ['device_token' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['device_token']);
    }
}

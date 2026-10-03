<?php

namespace Tests\Feature\Api\Farmer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * UC-FAPI-02 alternative flow D: 10 attempts per IP per minute.
 */
class LoginThrottleTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The throttle counter lives in the cache. phpunit.xml uses the array
        // store, which is per-application-instance and so already fresh per
        // test, but flush explicitly so the budget can never be inherited.
        Cache::flush();
    }

    public function test_eleventh_failed_login_in_a_minute_is_throttled(): void
    {
        $this->makeFarmer();

        $payload = ['email' => 'farmer@example.com', 'password' => 'wrong-password'];

        // Each of the first ten attempts must reach the controller and be
        // answered on its merits, not blocked.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/v1/farmer/login', $payload)->assertStatus(401);
        }

        $this->postJson('/api/v1/farmer/login', $payload)
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many login attempts. Please try again later.');
    }

    public function test_throttle_counts_attempts_not_failures(): void
    {
        $this->makeFarmer();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/v1/farmer/login', [
                'email'    => 'farmer@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // Correct credentials, but the budget for this IP is already spent.
        $this->postJson('/api/v1/farmer/login', [
            'email'    => 'farmer@example.com',
            'password' => 'password123',
        ])->assertStatus(429);
    }
}

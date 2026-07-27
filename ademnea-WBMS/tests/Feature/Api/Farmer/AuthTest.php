<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\User;
use App\Models\Farmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_register(): void
    {
        $data = [
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'telephone' => '256700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/farmer/register', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Registration submitted. Awaiting admin approval.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'farmer@example.com',
            'role' => 'farmer',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('farmers', [
            'telephone' => '256700000000',
        ]);
    }

    public function test_register_requires_valid_email(): void
    {
        $data = [
            'name' => 'Test Farmer',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/farmer/register', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        $data = [
            'name' => 'Test Farmer',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/farmer/register', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_farmer_can_login(): void
    {
        $user = User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        Farmer::create([
            'user_id' => $user->id,
            'telephone' => '256700000000',
        ]);

        $response = $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'expires_at',
                'farmer' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_login_rejects_pending_account(): void
    {
        $user = User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => 'pending',
        ]);

        Farmer::create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account is awaiting administrator approval.',
            ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        Farmer::create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/v1/farmer/login', [
            'email' => 'farmer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_farmer_can_logout(): void
    {
        $user = User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        $farmer = Farmer::create([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/farmer/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/farmer/logout');

        $response->assertStatus(401);
    }
}
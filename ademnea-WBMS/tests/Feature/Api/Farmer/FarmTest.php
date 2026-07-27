<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\User;
use App\Models\Farmer;
use App\Models\Farm;
use App\Models\Hive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthenticatedFarmer(): array
    {
        $user = User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => bcrypt('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        $farmer = Farmer::create([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'user' => $user,
            'farmer' => $farmer,
            'token' => $token,
        ];
    }

    public function test_farmer_can_view_own_farms(): void
    {
        $auth = $this->createAuthenticatedFarmer();

        Farm::create([
            'farmer_id' => $auth['farmer']->id,
            'name' => 'My Farm',
            'district' => 'Kampala',
            'address' => '123 Main St',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $auth['token'])
            ->getJson('/api/v1/farmer/farms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'district', 'hives_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('My Farm', $response->json('data.0.name'));
    }

    public function test_farmer_cannot_view_other_farms(): void
    {
        $auth = $this->createAuthenticatedFarmer();

        // Create farm for a different farmer
        $otherUser = User::create([
            'name' => 'Other Farmer',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        $otherFarmer = Farmer::create([
            'user_id' => $otherUser->id,
        ]);

        Farm::create([
            'farmer_id' => $otherFarmer->id,
            'name' => 'Other Farm',
            'district' => 'Jinja',
            'address' => '456 Other St',
        ]);

        // Create farm for authenticated farmer
        Farm::create([
            'farmer_id' => $auth['farmer']->id,
            'name' => 'My Farm',
            'district' => 'Kampala',
            'address' => '123 Main St',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $auth['token'])
            ->getJson('/api/v1/farmer/farms');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('My Farm', $response->json('data.0.name'));
    }

    public function test_farmer_can_view_hives_in_farm(): void
    {
        $auth = $this->createAuthenticatedFarmer();

        $farm = Farm::create([
            'farmer_id' => $auth['farmer']->id,
            'name' => 'My Farm',
            'district' => 'Kampala',
            'address' => '123 Main St',
        ]);

        Hive::create([
            'farm_id' => $farm->id,
            'name' => 'Hive 1',
            'latitude' => 0.3136,
            'longitude' => 32.5811,
        ]);

        Hive::create([
            'farm_id' => $farm->id,
            'name' => 'Hive 2',
            'latitude' => 0.3137,
            'longitude' => 32.5812,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $auth['token'])
            ->getJson('/api/v1/farmer/farms/' . $farm->id . '/hives');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('Hive 1', $response->json('data.0.name'));
        $this->assertEquals('Hive 2', $response->json('data.1.name'));
    }

    public function test_farmer_cannot_view_hives_in_other_farm(): void
    {
        $auth = $this->createAuthenticatedFarmer();

        // Create farm for a different farmer
        $otherUser = User::create([
            'name' => 'Other Farmer',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'role' => 'farmer',
            'status' => 'active',
        ]);

        $otherFarmer = Farmer::create([
            'user_id' => $otherUser->id,
        ]);

        $otherFarm = Farm::create([
            'farmer_id' => $otherFarmer->id,
            'name' => 'Other Farm',
            'district' => 'Jinja',
            'address' => '456 Other St',
        ]);

        Hive::create([
            'farm_id' => $otherFarm->id,
            'name' => 'Other Hive',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $auth['token'])
            ->getJson('/api/v1/farmer/farms/' . $otherFarm->id . '/hives');

        $response->assertStatus(403);
    }
}
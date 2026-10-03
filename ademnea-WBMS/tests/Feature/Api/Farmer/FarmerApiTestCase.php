<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Shared fixtures for the farmer mobile API.
 *
 * Note the deliberate id offset in makeFarmer(): several endpoints key
 * farmer-owned data by farmers.id while the request authenticates a User.
 * If users.id and farmers.id happen to match, passing the wrong one is
 * invisible. Every farmer created here therefore has a farmers.id that
 * cannot equal its users.id.
 */
abstract class FarmerApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('farmer', 'web');
        Role::findOrCreate('farmer-write', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{0: User, 1: Farmer}
     */
    protected function makeFarmer(
        string $email = 'farmer@example.com',
        string $status = 'active',
        bool $withRole = true,
        int $idOffset = 0
    ): array {
        // Burn farmer ids so farmers.id can never coincide with users.id.
        for ($i = 0; $i < 3 + $idOffset; $i++) {
            Farmer::factory()->create();
        }

        $user = User::create([
            'name'     => 'Test Farmer',
            'email'    => $email,
            'password' => Hash::make('password123'),
            'role'     => 'farmer',
            'status'   => $status,
        ]);

        $farmer = Farmer::create([
            'user_id'        => $user->id,
            'email'          => $email,
            'first_name'     => 'Test',
            'last_name'      => 'Farmer',
            'telephone'      => '256700000000',
            'status'         => $status === 'active' ? 'Active' : 'Inactive',
            'profile_status' => $status === 'active' ? 'active' : 'pending',
        ]);

        if ($withRole) {
            $user->assignRole('farmer');
        }

        return [$user, $farmer];
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    protected function authed(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->tokenFor($user))
            ->withHeader('Accept', 'application/json');
    }
}

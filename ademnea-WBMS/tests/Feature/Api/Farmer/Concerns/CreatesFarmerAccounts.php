<?php

namespace Tests\Feature\Api\Farmer\Concerns;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

trait CreatesFarmerAccounts
{
    /**
     * A farmer login exactly as registration + approval leave it:
     * User (role farmer, Spatie role farmer) linked to a Farmer profile.
     *
     * @return array{user: User, farmer: Farmer, token: string}
     */
    protected function createFarmerAccount(string $status = 'active', string $email = 'farmer@example.com'): array
    {
        // Admin-managed farmers without logins, so farmer ids never equal user ids
        // by accident (which would hide user-id-vs-farmer-id mix-ups).
        Farmer::factory()->count(2)->create();

        $user = User::create([
            'name' => 'Test Farmer',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'status' => $status,
        ]);
        $user->assignRole(Role::findOrCreate('farmer', 'web'));

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Farmer',
            'email' => $email,
            'telephone' => '256700000000',
            'profile_status' => $status === 'active' ? 'active' : 'pending',
        ]);

        return [
            'user' => $user,
            'farmer' => $farmer,
            'token' => $user->createToken('test-token')->plainTextToken,
        ];
    }

    protected function asFarmer(array $account): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$account['token']);
    }
}

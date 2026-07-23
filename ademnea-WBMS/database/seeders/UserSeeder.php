<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'farmer@ademnea.ac.ug'],
            [
                'name' => 'Test Farmer',
                'password' => Hash::make('password'),
                'role' => 'farmer',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'officer@ademnea.ac.ug'],
            [
                'name' => 'Test Field Officer',
                'password' => Hash::make('password'),
                'role' => 'field_officer',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FarmerRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'farmer', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'farmer-write', 'guard_name' => 'sanctum']);
    }
}
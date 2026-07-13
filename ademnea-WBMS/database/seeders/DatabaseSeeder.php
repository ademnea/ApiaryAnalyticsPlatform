<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // SuperAdminSeeder provisions all permissions, roles, and the initial super-admin user.
        // It is idempotent — safe to run multiple times.
        $this->call([
            AlertThresholdSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * SuperAdminSeeder
 *
 * File: database/seeders/SuperAdminSeeder.php
 *
 * Seeds the initial super-admin user and the full set of system permissions.
 * This seeder is IDEMPOTENT — running it multiple times will not create duplicates.
 *
 * Usage:
 *   php artisan db:seed --class=SuperAdminSeeder
 *
 * Or via DatabaseSeeder:
 *   php artisan migrate --seed
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * The complete set of system permissions.
     * Add new entries here when adding new protected modules.
     * Never create/delete permissions from the admin UI — only via seeders.
     */
    private array $permissions = [
        // User & access management
        'manage-users',
        'manage-roles',

        // Apiary & hive management
        'manage-apiaries',
        'manage-hives',
        'manage-iot-devices',
        'view-hive-data',

        // Farmer management
        'manage-farmers',
        'approve-farmer-registrations',

        // Monitoring & anomaly
        'view-monitoring-dashboard',
        'view-device-fleet',
        'view-anomaly-analytics',

        // Content management
        'manage-newsletter',
        'manage-publications',
        'manage-events',
        'manage-gallery',
        'manage-scholarship',
        'manage-work-packages',
        'manage-team-profiles',

        // Communication
        'manage-feedback',
        'manage-farmer-messages',

        // Reports
        'generate-reports',
    ];

    public function run(): void
    {
        // Clear Spatie permission cache to prevent stale cache issues on re-runs
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ----------------------------------------------------------------
        // 1. Seed permissions (idempotent — skip existing)
        // ----------------------------------------------------------------
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            foreach ($this->permissions as $permission) {
                try {
                    \Spatie\Permission\Models\Permission::firstOrCreate(
                        ['name' => $permission, 'guard_name' => 'web']
                    );
                } catch (\Throwable $e) {
                    Log::warning(
                        "SuperAdminSeeder: failed to create permission [{$permission}]: " . $e->getMessage()
                    );
                    $this->command->warn("  ⚠ Could not seed permission [{$permission}]: " . $e->getMessage());
                }
            }

            // ----------------------------------------------------------------
            // 2. Create or update the super-admin role with ALL permissions
            // ----------------------------------------------------------------
            $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );
            $superAdminRole->syncPermissions($this->permissions);

            // Also create the standard farmer roles
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'farmer', 'guard_name' => 'web']
            );
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'farmer-write', 'guard_name' => 'web']
            );
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'field-officer', 'guard_name' => 'web']
            );
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'researcher', 'guard_name' => 'web']
            );

            $this->command->info('✓ Permissions and roles seeded.');
        } else {
            $this->command->warn(
                'Spatie Laravel-Permission is not installed. ' .
                'Run: composer require spatie/laravel-permission ' .
                'then re-run this seeder.'
            );
        }

        // ----------------------------------------------------------------
        // 3. Create the super-admin user (idempotent)
        // ----------------------------------------------------------------
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@ademnea.ac.ug'],
            [
                'name'       => 'AdEMNEA Administrator',
                'password'   => Hash::make('AdEMNEA@2026!'),
                'role'       => 'admin',
                'status'     => 'active',
                'is_active'  => true,
            ]
        );

        // Assign the super-admin role if Spatie is installed
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $admin->syncRoles(['super-admin']);
        }

        $this->command->info('✓ Super-admin user created.');
        $this->command->info('  Email:    admin@ademnea.ac.ug');
        $this->command->info('  Password: AdEMNEA@2026!');
        $this->command->warn('  ⚠ Change this password immediately after first login.');
    }
}
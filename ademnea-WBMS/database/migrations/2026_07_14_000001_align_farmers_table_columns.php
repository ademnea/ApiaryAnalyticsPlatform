<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the `farmers` table with the Admin/ApiaryManagement module's
 * Farmer model and DashboardService.
 *
 * After the July-14 merge, `farmers` was rebuilt by the Farmer-API module
 * migration (2026_07_02_000000_create_farmers_table) which only has:
 *   id, user_id, telephone, address, gender, fcm_token, timestamps, deleted_at
 *
 * The Admin module's Farmer model and all admin controllers expect:
 *   first_name, last_name, email, phone / phone_number, country, region,
 *   village, national_id, photo_path, id_document_path, status,
 *   profile_status, is_active, farmer_code, registration_date, last_login_at
 *
 * All new columns are nullable so existing rows (user_id-based profiles)
 * are not affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {

            if (!Schema::hasColumn('farmers', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('farmers', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('farmers', 'email')) {
                $table->string('email', 255)->nullable()->unique()->after('last_name');
            }
            if (!Schema::hasColumn('farmers', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('farmers', 'phone_number')) {
                $table->string('phone_number', 20)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('farmers', 'phone_secondary')) {
                $table->string('phone_secondary', 20)->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('farmers', 'country')) {
                $table->string('country', 2)->default('UG')->after('phone_secondary');
            }
            if (!Schema::hasColumn('farmers', 'region')) {
                $table->string('region', 100)->nullable()->after('country');
            }
            if (!Schema::hasColumn('farmers', 'village')) {
                $table->string('village', 100)->nullable()->after('region');
            }
            if (!Schema::hasColumn('farmers', 'national_id')) {
                $table->string('national_id', 50)->nullable()->unique()->after('village');
            }
            if (!Schema::hasColumn('farmers', 'id_document_path')) {
                $table->string('id_document_path', 255)->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('farmers', 'photo_path')) {
                $table->string('photo_path', 255)->nullable()->after('id_document_path');
            }
            if (!Schema::hasColumn('farmers', 'status')) {
                $table->string('status', 20)->default('Active')->after('photo_path');
            }
            if (!Schema::hasColumn('farmers', 'profile_status')) {
                $table->string('profile_status', 20)->default('active')
                    ->comment('active | pending | incomplete')
                    ->after('status');
            }
            if (!Schema::hasColumn('farmers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('profile_status');
            }
            if (!Schema::hasColumn('farmers', 'farmer_code')) {
                $table->string('farmer_code', 30)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('farmers', 'registration_date')) {
                $table->timestamp('registration_date')->nullable();
            }
            if (!Schema::hasColumn('farmers', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });

        // CHECK constraint on status to enforce valid values.
        try {
            DB::statement("ALTER TABLE farmers ADD CONSTRAINT chk_farmers_status
                CHECK (status IN ('Active','Inactive','Suspended'))");
        } catch (\Throwable $e) {
            // Constraint already exists — safe to ignore.
        }

        // Index on status for DashboardService queries.
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_farmers_status ON farmers (status)');
        } catch (\Throwable $e) {
            // Already exists.
        }
    }

    public function down(): void
    {
        $added = [
            'first_name', 'last_name', 'email', 'phone', 'phone_number',
            'phone_secondary', 'country', 'region', 'village', 'national_id',
            'id_document_path', 'photo_path', 'status', 'profile_status',
            'is_active', 'farmer_code', 'registration_date', 'last_login_at',
        ];

        Schema::table('farmers', function (Blueprint $table) use ($added) {
            foreach ($added as $col) {
                if (Schema::hasColumn('farmers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        try {
            DB::statement('ALTER TABLE farmers DROP CONSTRAINT IF EXISTS chk_farmers_status');
        } catch (\Throwable $e) {
        }
    }
};

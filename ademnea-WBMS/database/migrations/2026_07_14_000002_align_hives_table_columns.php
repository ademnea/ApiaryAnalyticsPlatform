<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the `hives` table with the Admin/ApiaryManagement module's
 * Hive model, HiveRegistrationService, and DashboardService.
 *
 * After the July-14 merge, `hives` was built by the Farmer-API module
 * migration (2026_07_08_000011) which has:
 *   id, farm_id, name, latitude, longitude, status, connected, colonized,
 *   type, installation_date, colonization_date, bee_species, notes,
 *   deleted_at, timestamps
 *
 * The Admin module expects:
 *   apiary_id, hybrid_identifier / hive_code, display_name, hive_type,
 *   construction_material, colony_origin, queen_status, current_status,
 *   gps_latitude, gps_longitude, gps_accuracy_meters, last_inspection_date
 *
 * Both old and new column names are kept for backward compat.
 * current_status is back-filled from the existing status column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hives', function (Blueprint $table) {

            if (!Schema::hasColumn('hives', 'apiary_id')) {
                // Nullable so existing rows (farm_id based) are unaffected.
                $table->unsignedBigInteger('apiary_id')->nullable()->after('id');

                // Only add FK if apiaries table exists (it should at this point).
                if (Schema::hasTable('apiaries')) {
                    $table->foreign('apiary_id', 'fk_hives_apiary_id')
                        ->references('id')->on('apiaries')
                        ->onDelete('cascade');
                }
            }

            if (!Schema::hasColumn('hives', 'hybrid_identifier')) {
                $table->string('hybrid_identifier', 50)->nullable()->after('apiary_id');
            }
            if (!Schema::hasColumn('hives', 'hive_code')) {
                $table->string('hive_code', 50)->nullable()->after('hybrid_identifier');
            }
            if (!Schema::hasColumn('hives', 'display_name')) {
                $table->string('display_name', 150)->nullable()->after('hive_code');
            }
            if (!Schema::hasColumn('hives', 'hive_type')) {
                $table->string('hive_type', 50)->default('Langstroth')->after('display_name');
            }
            if (!Schema::hasColumn('hives', 'construction_material')) {
                $table->string('construction_material', 100)->nullable()->after('hive_type');
            }
            if (!Schema::hasColumn('hives', 'colony_origin')) {
                $table->string('colony_origin', 50)->nullable()->after('construction_material');
            }
            if (!Schema::hasColumn('hives', 'queen_status')) {
                $table->string('queen_status', 50)->default('Unknown')->after('colony_origin');
            }
            if (!Schema::hasColumn('hives', 'current_status')) {
                $table->string('current_status', 50)->default('Active')->after('queen_status');
            }
            if (!Schema::hasColumn('hives', 'gps_latitude')) {
                $table->decimal('gps_latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('hives', 'gps_longitude')) {
                $table->decimal('gps_longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('hives', 'gps_accuracy_meters')) {
                $table->decimal('gps_accuracy_meters', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('hives', 'last_inspection_date')) {
                $table->date('last_inspection_date')->nullable();
            }
        });

        // Back-fill current_status from existing status column.
        DB::statement("UPDATE hives SET current_status = status WHERE current_status = 'Active'");

        // Back-fill gps columns from latitude/longitude if present.
        if (Schema::hasColumn('hives', 'latitude') && Schema::hasColumn('hives', 'gps_latitude')) {
            DB::statement('UPDATE hives SET gps_latitude = latitude WHERE gps_latitude IS NULL AND latitude IS NOT NULL');
            DB::statement('UPDATE hives SET gps_longitude = longitude WHERE gps_longitude IS NULL AND longitude IS NOT NULL');
        }

        // Index on current_status for DashboardService queries.
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_hives_current_status ON hives (current_status)');
        } catch (\Throwable $e) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_hives_apiary_id ON hives (apiary_id)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $added = [
            'apiary_id', 'hybrid_identifier', 'hive_code', 'display_name',
            'hive_type', 'construction_material', 'colony_origin', 'queen_status',
            'current_status', 'gps_latitude', 'gps_longitude', 'gps_accuracy_meters',
            'last_inspection_date',
        ];

        Schema::table('hives', function (Blueprint $table) use ($added) {
            try {
                $table->dropForeign('fk_hives_apiary_id');
            } catch (\Throwable $e) {
            }
            foreach ($added as $col) {
                if (Schema::hasColumn('hives', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

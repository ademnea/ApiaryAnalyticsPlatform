<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures hives.hive_code is nullable and back-fills it from hybrid_identifier.
 * After the merge hive_code is added nullable by 000002, so this is a
 * no-op guard for consistency, plus the back-fill SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('hives', 'hive_code')) {
            Schema::table('hives', function (Blueprint $table) {
                $table->string('hive_code', 50)->nullable()->change();
            });

            // Back-fill hive_code from hybrid_identifier for any existing rows.
            if (Schema::hasColumn('hives', 'hybrid_identifier')) {
                DB::statement('UPDATE hives SET hive_code = hybrid_identifier WHERE hive_code IS NULL AND hybrid_identifier IS NOT NULL');
            }
        }
    }

    public function down(): void
    {
        // Nothing to reverse.
    }
};

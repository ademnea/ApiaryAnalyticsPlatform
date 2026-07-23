<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures the `apiaries` table has all columns expected by the Admin module.
 * After the July-14 merge the table was rebuilt correctly by
 * 2026_07_08_053520_create_apiaries_table (farmer_id, apiary_code, district,
 * description, status all present). This migration is now a no-op guard
 * kept for migration history consistency.
 */
return new class extends Migration
{
    public function up(): void
    {
        // All columns already present — add any still-missing ones defensively.
        Schema::table('apiaries', function (Blueprint $table) {
            if (!Schema::hasColumn('apiaries', 'farmer_id')) {
                $table->unsignedBigInteger('farmer_id')->nullable();
                $table->foreign('farmer_id', 'fk_apiaries_farmer_id')
                    ->references('id')->on('farmers')->onDelete('set null');
                $table->index('farmer_id', 'idx_apiaries_farmer_id');
            }
            if (!Schema::hasColumn('apiaries', 'apiary_code')) {
                $table->string('apiary_code', 10)->nullable()->unique();
            }
            if (!Schema::hasColumn('apiaries', 'district')) {
                $table->string('district', 100)->nullable();
            }
            if (!Schema::hasColumn('apiaries', 'description')) {
                $table->text('description')->nullable();
            }
        });

        // Normalise status casing.
        try {
            DB::statement("UPDATE apiaries SET status = 'Active' WHERE LOWER(status) = 'active'");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        // No destructive rollback needed.
    }
};

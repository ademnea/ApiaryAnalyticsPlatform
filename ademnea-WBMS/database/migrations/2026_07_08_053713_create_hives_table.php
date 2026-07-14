<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: hives
     * Purpose: Individual hive registry linked to apiaries; central
     * reference for all hive-related data (inspections, harvests,
     * device assignments, alert thresholds).
     * Soft delete: Yes (Rule 5) — see Decision 4: hive records are soft
     * deleted to preserve linked historical data, but sensor data
     * (owned by Developer C) is append-only and never deleted.
     */
    public function up(): void
    {
        Schema::create('hives', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('apiary_id');
            $table->foreign('apiary_id')
                ->references('id')->on('apiaries')
                ->onDelete('cascade');

            $table->string('hybrid_identifier', 50)->unique();
            $table->string('display_name', 150);

            $table->enum('hive_type', ['TopBar', 'Langstroth', 'Warre', 'Kenya', 'Other'])
                ->default('Langstroth');
            $table->string('construction_material', 100)->nullable();
            $table->date('installation_date')->nullable();

            $table->enum('colony_origin', ['Wild Capture', 'Split', 'Package', 'NUC', 'Unknown'])
                ->nullable();
            $table->enum('queen_status', ['Present', 'Absent', 'New', 'Old', 'Superseded', 'Unknown'])
                ->default('Unknown');

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            $table->enum('current_status', [
                'Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned',
            ])->default('Active');

            $table->date('last_inspection_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('apiary_id', 'idx_hives_apiary_id');
            $table->index('current_status', 'idx_hives_current_status');

            $table->index(['latitude', 'longitude'], 'idx_hives_coords');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hives');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: hives
     * Purpose: Individual hive registry linked to apiaries.
     * Soft delete: Yes (Rule 5).
     *
     * Admin/ApiaryManagement module schema (apiary_id, admin-specific
     * columns). The earlier Farmer-API module's parallel farm_id column
     * (Farm → Hive hierarchy) has been retired in favour of Apiary as the
     * single physical-site model.
     */
    public function up(): void
    {
        if (Schema::hasTable('hives')) {
            return; // Table already exists — kept for safety on re-runs.
        }

        Schema::create('hives', function (Blueprint $table) {
            $table->id();

            // Relationship — apiary is the single physical-site model.
            $table->unsignedBigInteger('apiary_id')->nullable();
            $table->foreign('apiary_id', 'fk_hives_apiary_id')
                ->references('id')->on('apiaries')
                ->onDelete('cascade');

            // Identification
            $table->string('hybrid_identifier', 50)->nullable()->unique();
            $table->string('hive_code', 50)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('name', 150)->nullable();

            // Hive type
            $table->enum('hive_type', ['TopBar', 'Langstroth', 'Warre', 'Kenya', 'Other'])
                ->default('Langstroth');
            $table->string('construction_material', 100)->nullable();

            // Colony info
            $table->enum('colony_origin', ['Wild Capture', 'Split', 'Package', 'NUC', 'Unknown'])
                ->nullable();
            $table->enum('queen_status', ['Present', 'Absent', 'New', 'Old', 'Superseded', 'Unknown'])
                ->default('Unknown');

            // Status — legacy + current admin status
            $table->string('status')->default('active');
            $table->enum('current_status', [
                'Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned',
            ])->default('Active');

            // GPS — high-precision location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();

            // Inspection
            $table->date('installation_date')->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('colonization_date')->nullable();

            // Bee species (farmer-specific)
            $table->string('bee_species', 100)->nullable();

            // Flags (farmer-specific, nullable for admin)
            $table->boolean('connected')->nullable();
            $table->boolean('colonized')->nullable();
            $table->string('type', 50)->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('apiary_id', 'idx_hives_apiary_id');
            $table->index('current_status', 'idx_hives_current_status');
            $table->index(['latitude', 'longitude'], 'idx_hives_coords');
            $table->index('hybrid_identifier', 'idx_hives_hybrid_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hives');
    }
};

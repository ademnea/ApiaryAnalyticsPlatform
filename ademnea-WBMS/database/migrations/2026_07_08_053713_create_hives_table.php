<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hives', function (Blueprint $table) {
            $table->id();
            
            // FK to parent apiary
            $table->unsignedBigInteger('apiary_id');
            
            // Hive identification
            $table->string('hive_code', 50)->unique(); // HIVE-UG-MUK-001 format, system-generated
            $table->string('display_name', 150); // Human-readable name: "Queen Colony A"
            
            // Hive details
            $table->string('hive_type', 50); // Langstroth, Top-Bar, Warre, Skep, etc.
            $table->string('construction_material', 100)->nullable();
            $table->date('installation_date')->nullable();
            $table->string('colony_origin', 100)->nullable(); // Wild capture, purchased package, split, etc.
            
            // Current state
            $table->string('queen_status', 20)->nullable(); // present, absent, queenless, unknown
            $table->string('status', 30)->default('active')->index(); // active, inactive, under_inspection, queenless, absconded, decommissioned
            
            // GPS coordinates (required for field navigation and mapping)
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->integer('gps_accuracy_meters')->nullable(); // Optional accuracy radius
            
            // Denormalized field for dashboard queries
            $table->date('last_inspection_date')->nullable(); // Updated by InspectionService after each inspection
            
            // Soft delete
            $table->softDeletes();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key to apiaries
            $table->foreign('apiary_id')
                ->references('id')
                ->on('apiaries')
                ->onDelete('restrict'); // Prevent deletion of apiary while hives exist
            
            // Indexes
            $table->index('apiary_id');
            $table->index('status'); // Fast filtering for active vs. offline hives
            $table->index(['gps_latitude', 'gps_longitude']); // Spatial queries for mapping
            $table->index('last_inspection_date'); // "Hives needing inspection" queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hives');
    }
};
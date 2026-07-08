<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apiaries', function (Blueprint $table) {
            $table->id();
            
            // Core fields
            // FK to farmer (added per corrected order)
            $table->unsignedBigInteger('farmer_id')->nullable();
            $table->string('name', 150);
            $table->string('country', 100);
            $table->string('region', 100)->nullable();
            $table->string('managing_entity', 150)->nullable();
            
            // Capacity and contact
            $table->integer('hive_capacity')->default(0);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 255)->nullable();
            
            // Status fields
            $table->string('status', 20)->default('active')->index(); // active, inactive, decommissioned
            $table->boolean('is_active')->default(true);
            
            // Soft delete (available but not typically used)
            $table->softDeletes();
            
            // Timestamps
            $table->timestamps();
            
            // Unique constraint: no duplicate apiaries under same managing entity + country
            $table->unique(['name', 'country', 'managing_entity']);

            // Indexes
            // Foreign key to farmers table (NEW)
            $table->foreign('farmer_id')
                ->references('id')
                ->on('farmers')
                ->onDelete('set null');

            $table->index('farmer_id'); // critical: query farmer's apiaries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apiaries');
    }
};
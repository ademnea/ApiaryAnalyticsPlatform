<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE: This migration (from the Farmer API module) defines a different
 * `hives` schema (farm_id FK, no apiary_id). The apiary-based hives table
 * already exists on this DB (created before this migration was added).
 * We skip creation to avoid overwriting the existing table.
 *
 * The `farms` table created by 2026_07_08_000010 provides the farm_id
 * reference; existing hive data uses apiary_id and is managed by the
 * Admin/ApiaryManagement module.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hives')) {
            return; // Already exists with the apiary-based schema.
        }

        Schema::create('hives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farm_id');
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('active');
            $table->boolean('connected')->default(true);
            $table->boolean('colonized')->default(true);
            $table->string('type')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('colonization_date')->nullable();
            $table->string('bee_species')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('farm_id')
                ->references('id')->on('farms')
                ->onDelete('cascade');
            $table->index('farm_id');
        });
    }

    public function down(): void
    {
        // Only drop if it was actually created by this migration (i.e., has farm_id).
        if (Schema::hasColumn('hives', 'farm_id')) {
            Schema::dropIfExists('hives');
        }
    }
};

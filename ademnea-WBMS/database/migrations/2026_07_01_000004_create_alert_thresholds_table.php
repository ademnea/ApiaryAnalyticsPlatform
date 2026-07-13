<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REQ-F-FAPI-24: Threshold values consulted by the hourly scheduled job.
 * Admin-configurable without code changes.
 *
 * Owned by: Developer D (Farmer Mobile API) — admin UI for editing this table
 * is out of scope here and belongs to the Dashboard module, but the schema
 * and the job that reads it are part of the Farmer API alerts sub-module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_thresholds', function (Blueprint $table) {
            $table->id();

            // e.g. 'feed_required_weight_kg', 'feed_required_honey_stores'
            $table->string('key', 100)->unique();
            $table->string('value', 255);
            $table->string('description', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_thresholds');
    }
};

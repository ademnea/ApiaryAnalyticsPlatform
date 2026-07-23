<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * hives.farm_id was created NOT NULL by the Farmer-API module migration
 * (2026_07_08_000011_create_hives_table). The Admin/ApiaryManagement module
 * uses apiary_id instead and never sets farm_id, causing a NOT NULL violation
 * on every hive insert through the admin panel.
 *
 * Fix: make farm_id nullable so admin-created hives (apiary_id based)
 * can be inserted without a farm_id value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hives', function (Blueprint $table) {
            $table->unsignedBigInteger('farm_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hives', function (Blueprint $table) {
            $table->unsignedBigInteger('farm_id')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several columns on `hives` were created NOT NULL by the Farmer-API module
 * migration (2026_07_08_000011_create_hives_table):
 *
 *   name       NOT NULL  — admin module uses `display_name` instead
 *   connected  NOT NULL  — boolean device flag not relevant to admin module
 *   colonized  NOT NULL  — boolean colony flag not relevant to admin module
 *
 * The admin/ApiaryManagement module never sets these, causing NOT NULL
 * violations on every hive insert through the admin panel.
 *
 * Fix: make all three nullable with sensible defaults for existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hives', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->boolean('connected')->default(true)->nullable()->change();
            $table->boolean('colonized')->default(true)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hives', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->boolean('connected')->nullable(false)->change();
            $table->boolean('colonized')->nullable(false)->change();
        });
    }
};

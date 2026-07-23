<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * farmers.user_id was created NOT NULL by the Farmer-API module migration
 * (2026_07_02_000000_create_farmers_table). The Admin/ApiaryManagement module
 * creates standalone Farmer records that are not linked to a User account,
 * so user_id is never set by the admin panel and causes a NOT NULL violation.
 *
 * Fix: make user_id nullable so admin-created farmers can exist without
 * a corresponding User record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};

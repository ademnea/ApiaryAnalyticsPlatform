<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_temperatures', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id', 'fk_hive_temperatures_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_hive_temperatures_device')
                ->references('id')->on('iot_devices')
                ->onDelete('restrict');

            // Discrete typed columns per Schema Rule 7 — no *-delimited string.
            $table->float('honey_section')->nullable();
            $table->float('brood_section')->nullable();
            $table->float('exterior')->nullable();

            $table->boolean('suspect')->default(false);

            $table->timestamp('recorded_at'); // device-reported UTC reading time
            $table->timestamp('created_at')->useCurrent(); // server ingestion time — append-only, no updated_at

            // Mandatory compound index per Schema Rule 6.
            $table->index(['hive_id', 'created_at']);
            $table->index('device_id');

            // Idempotency guard — see idempotency migration for the unique constraint.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_temperatures');
    }
};
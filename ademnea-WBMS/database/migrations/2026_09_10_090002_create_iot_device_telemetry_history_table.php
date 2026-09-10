<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_device_telemetry_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_iot_device_telemetry_history_device')
                ->references('id')->on('iot_devices')
                ->onDelete('cascade');

            // Mirrors iot_device_telemetry's per-heartbeat fields, copied
            // verbatim at insert time. Excludes firmware_version and the
            // server-computed data_gap_minutes/submission_interval_actual —
            // those are current-device-state fields, not history.
            $table->float('battery_level')->nullable();
            $table->float('signal_strength')->nullable();
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->float('cpu_usage')->nullable();
            $table->float('storage_usage')->nullable();
            $table->unsignedInteger('reboot_count')->default(0);
            $table->float('sensor_read_success_rate')->nullable();
            $table->json('error_codes')->nullable();

            $table->timestamp('recorded_at'); // the heartbeat's own timestamp
            $table->timestamp('created_at')->useCurrent(); // append-only, no updated_at

            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_device_telemetry_history');
    }
};

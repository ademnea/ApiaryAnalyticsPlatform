<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_device_telemetry', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id')->unique();
            $table->foreign('device_id', 'fk_iot_device_telemetry_device')
                ->references('id')->on('iot_devices')
                ->onDelete('cascade');

            $table->float('battery_level')->nullable();        // %
            $table->float('signal_strength')->nullable();      // dBm (RSSI)
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->string('firmware_version', 30)->nullable();
            $table->float('cpu_usage')->nullable();             // %
            $table->float('storage_usage')->nullable();         // %
            $table->unsignedInteger('reboot_count')->default(0);
            $table->float('sensor_read_success_rate')->nullable(); // ratio 0–1
            $table->json('error_codes')->nullable();

            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_data_received_at')->nullable();

            // Server-computed by the IoT Condition Monitoring module's
            // gap-detection job (§4.4/4.6) — this module writes the columns,
            // that module owns the logic that fills them.
            $table->unsignedInteger('data_gap_minutes')->nullable();
            $table->float('submission_interval_actual')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_device_telemetry');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_anomalies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_sensor_anomalies_device')
                ->references('id')->on('iot_devices')
                ->onDelete('restrict');

            // Nullable: a device-telemetry anomaly (e.g. low_battery) may not
            // resolve to a hive if the device is unassigned.
            $table->unsignedBigInteger('hive_id')->nullable();
            $table->foreign('hive_id', 'fk_sensor_anomalies_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->string('sensor_type'); // temperature|humidity|co2|weight|telemetry
            $table->string('anomaly_type'); // static_threshold_breach|frozen_sensor|statistical_deviation|low_battery|critical_battery|weak_signal|reboot_loop|storage_full|device_offline|...
            $table->float('anomaly_score')->nullable(); // 1.0 for rules-layer certainty; ML raw score for ML layers
            $table->json('record_value'); // the flagged value(s)
            $table->string('detection_layer'); // rules|ml|rf|lstm

            $table->timestamp('detected_at');
            $table->boolean('alerted')->default(false);
            $table->timestamp('alerted_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();

            $table->timestamp('created_at')->useCurrent(); // append-only, no updated_at — permanent evidence

            // Mandatory — serve "last 20 anomalies per device" (UC-IOT-10) and
            // the "7-day anomaly heatmap per hive" (UC-IOT-11) dashboard queries.
            $table->index(['hive_id', 'detected_at']);
            $table->index(['device_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_anomalies');
    }
};

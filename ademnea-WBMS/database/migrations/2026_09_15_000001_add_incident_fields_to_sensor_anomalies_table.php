<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns sensor_anomalies rows into incidents: one open row per
 * (device, hive, sensor_type, anomaly_type) that is touched on repeat
 * detections instead of inserting a new row per reading, plus an
 * acknowledge/resolve lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_anomalies', function (Blueprint $table) {
            // record_value keeps the first flagged value (evidence);
            // last_record_value tracks the most recent repeat.
            $table->timestamp('last_seen_at')->nullable()->after('detected_at');
            $table->unsignedInteger('occurrences')->default(1)->after('last_seen_at');
            $table->json('last_record_value')->nullable()->after('record_value');

            $table->timestamp('acknowledged_at')->nullable()->after('alerted_at');
            $table->unsignedBigInteger('acknowledged_by')->nullable()->after('acknowledged_at');
            $table->foreign('acknowledged_by', 'fk_sensor_anomalies_ack_user')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            $table->foreign('resolved_by', 'fk_sensor_anomalies_resolved_user')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->text('resolution_note')->nullable()->after('resolved_by');
            $table->boolean('auto_resolved')->default(false)->after('resolution_note');

            // Serves the "find the open incident for this device/type" lookup
            // run on every evaluated reading.
            $table->index(['device_id', 'anomaly_type', 'resolved'], 'idx_sensor_anomalies_open_incident');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_anomalies', function (Blueprint $table) {
            $table->dropIndex('idx_sensor_anomalies_open_incident');
            $table->dropForeign('fk_sensor_anomalies_ack_user');
            $table->dropForeign('fk_sensor_anomalies_resolved_user');
            $table->dropColumn([
                'last_seen_at', 'occurrences', 'last_record_value',
                'acknowledged_at', 'acknowledged_by',
                'resolved_by', 'resolution_note', 'auto_resolved',
            ]);
        });
    }
};

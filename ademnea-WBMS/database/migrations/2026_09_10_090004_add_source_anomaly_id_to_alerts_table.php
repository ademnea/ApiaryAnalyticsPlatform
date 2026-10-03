<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->unsignedBigInteger('source_anomaly_id')->nullable()->after('hive_id');
            $table->foreign('source_anomaly_id', 'fk_alerts_source_anomaly')
                ->references('id')->on('sensor_anomalies')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropForeign('fk_alerts_source_anomaly');
            $table->dropColumn('source_anomaly_id');
        });
    }
};

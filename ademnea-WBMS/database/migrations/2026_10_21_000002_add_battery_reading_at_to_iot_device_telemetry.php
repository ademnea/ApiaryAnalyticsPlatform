<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_device_telemetry', function (Blueprint $table) {
            $table->timestamp('battery_reading_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('iot_device_telemetry', function (Blueprint $table) {
            $table->dropColumn('battery_reading_at');
        });
    }
};

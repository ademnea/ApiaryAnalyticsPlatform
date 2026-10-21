<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->string('mac_address', 17)->nullable();
            $table->string('chip', 100)->nullable();
        });

        Schema::table('iot_device_telemetry', function (Blueprint $table) {
            $table->float('battery_voltage')->nullable();
            $table->float('battery_current')->nullable();
        });

        Schema::table('iot_device_telemetry_history', function (Blueprint $table) {
            $table->float('battery_voltage')->nullable();
            $table->float('battery_current')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('iot_device_telemetry_history', function (Blueprint $table) {
            $table->dropColumn(['battery_voltage', 'battery_current']);
        });

        Schema::table('iot_device_telemetry', function (Blueprint $table) {
            $table->dropColumn(['battery_voltage', 'battery_current']);
        });

        Schema::table('iot_devices', function (Blueprint $table) {
            $table->dropColumn(['mac_address', 'chip']);
        });
    }
};

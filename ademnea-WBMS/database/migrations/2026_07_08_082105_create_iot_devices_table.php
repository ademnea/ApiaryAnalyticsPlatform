<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_code', 50)->unique();
            $table->string('device_type', 50);

            $table->unsignedBigInteger('hardware_team_id');
            $table->foreign('hardware_team_id', 'fk_iot_devices_hardware_team')
                ->references('id')->on('iot_hardware_teams')
                ->onDelete('restrict');
            $table->index('hardware_team_id');

            $table->unsignedBigInteger('hive_id')->nullable();
            $table->foreign('hive_id', 'fk_iot_devices_hive')
                ->references('id')->on('hives')
                ->onDelete('set null');
            $table->index('hive_id');

            $table->string('api_key_hash', 255);

            $table->string('firmware_version', 30)->nullable();
            $table->string('firmware_notes', 255)->nullable();
            $table->string('hardware_revision', 30)->nullable();

            $table->unsignedInteger('expected_interval_minutes')->default(5);

            $table->string('status', 20)->default('provisioned');
            $table->boolean('active_flag')->default(true);
            $table->index('active_flag');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
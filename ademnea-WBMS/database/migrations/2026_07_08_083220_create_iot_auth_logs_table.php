<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_auth_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id')->nullable();
            $table->foreign('device_id', 'fk_iot_auth_logs_device')
                ->references('id')->on('iot_devices')
                ->onDelete('set null');

            $table->string('event_type', 20); // provisioned, revoked, reactivated, auth_success, auth_failure
            $table->string('ip_address', 45)->nullable();
            $table->string('endpoint', 100)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->index(['device_id', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_auth_logs');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_ingestion_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_iot_ingestion_logs_device')
                ->references('id')->on('iot_devices')
                ->onDelete('cascade');

            $table->string('payload_type', 20);  // sensor_data | heartbeat | media
            $table->string('outcome', 20);       // accepted | rejected_validation | rejected_auth
            $table->json('validation_errors')->nullable();
            $table->unsignedInteger('payload_size_bytes')->nullable();

            $table->timestamp('created_at')->useCurrent(); // append-only, no updated_at

            $table->index(['device_id', 'created_at']);
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_ingestion_logs');
    }
};
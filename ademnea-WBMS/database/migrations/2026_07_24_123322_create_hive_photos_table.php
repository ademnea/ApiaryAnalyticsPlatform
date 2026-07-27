<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_photos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id', 'fk_hive_photos_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_hive_photos_device')
                ->references('id')->on('iot_devices')
                ->onDelete('restrict');

            // file_path resolves to a fully-qualified HTTPS URL at the API
            // layer via the model accessor — never returned raw.
            $table->string('file_path', 255);
            $table->unsignedInteger('file_size_bytes')->nullable();

            $table->timestamp('recorded_at'); // device-reported capture time, UTC
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hive_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_photos');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_videos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id', 'fk_hive_videos_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_hive_videos_device')
                ->references('id')->on('iot_devices')
                ->onDelete('restrict');

            $table->string('file_path', 255);
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hive_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_videos');
    }
};
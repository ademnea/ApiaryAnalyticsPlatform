<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_weights', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id', 'fk_hive_weights_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->unsignedBigInteger('device_id');
            $table->foreign('device_id', 'fk_hive_weights_device')
                ->references('id')->on('iot_devices')
                ->onDelete('restrict');

            $table->float('weight_kg')->nullable();

            $table->boolean('suspect')->default(false);

            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hive_id', 'created_at']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_weights');
    }
};
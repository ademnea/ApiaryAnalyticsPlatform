<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('photo_path', 500);
            $table->string('photo_filename', 255);
            $table->unsignedSmallInteger('photo_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Foreign Keys
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');

            // Indexes
            $table->index('event_id');
            $table->index(['event_id', 'photo_order'], 'idx_event_photo_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_photos');
    }
};

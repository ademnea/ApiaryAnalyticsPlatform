<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFarmerMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farmer_id');
            $table->string('subject', 255);
            $table->text('message');
            $table->unsignedBigInteger('hive_id')->nullable();
            $table->enum('status', ['sent', 'seen_by_admin'])->default('sent');
            $table->timestamps();

            $table->foreign('farmer_id')
                ->references('id')
                ->on('farmers')
                ->onDelete('cascade');
            $table->foreign('hive_id')
                ->references('id')
                ->on('hives')
                ->onDelete('set null');
            $table->index('farmer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_messages');
    }
}
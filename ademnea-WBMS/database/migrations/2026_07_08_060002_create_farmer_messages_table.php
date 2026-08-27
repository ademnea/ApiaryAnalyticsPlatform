<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('farmer_messages')) {
            return; // Already created by an earlier migration on this DB.
        }

        Schema::create('farmer_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->onDelete('cascade');
            $table->string('subject', 255);
            $table->text('message');
            $table->foreignId('hive_id')->nullable()->constrained('hives')->nullOnDelete();
            $table->enum('status', ['sent', 'seen_by_admin'])->default('sent');
            $table->timestamps();

            $table->index('farmer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_messages');
    }
};

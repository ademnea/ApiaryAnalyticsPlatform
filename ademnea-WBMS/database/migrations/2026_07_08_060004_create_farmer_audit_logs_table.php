<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('farmer_audit_logs')) {
            return; // Already created by an earlier migration on this DB.
        }

        Schema::create('farmer_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->onDelete('cascade');
            $table->string('action_type');
            $table->string('affected_record_type');
            $table->unsignedBigInteger('affected_record_id')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'action_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_audit_logs');
    }
};

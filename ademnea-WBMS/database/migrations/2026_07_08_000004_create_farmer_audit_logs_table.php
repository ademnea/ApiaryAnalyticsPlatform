<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFarmerAuditLogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farmer_id');
            $table->string('action_type');
            $table->string('affected_record_type');
            $table->unsignedBigInteger('affected_record_id');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('farmer_id')
                ->references('id')
                ->on('farmers')
                ->onDelete('cascade');
            $table->index(['farmer_id', 'action_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_audit_logs');
    }
}
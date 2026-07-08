<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_status_history', function (Blueprint $table) {
            $table->id();
            
            // FK to hive
            $table->unsignedBigInteger('hive_id');
            
            // Status transition details
            $table->string('previous_status', 30); // Former status value
            $table->string('new_status', 30); // New status value
            
            // Who made the change
            $table->unsignedBigInteger('changed_by_user_id')->nullable(); // FK to users; nullable for system-initiated changes
            
            // Why the change happened
            $table->text('change_notes')->nullable();
            $table->string('reason_code', 50)->nullable(); // Structured code: inspection_finding, beekeeper_report, system_alert
            
            // Timestamp (only column that matters for this audit log)
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('hive_id')
                ->references('id')
                ->on('hives')
                ->onDelete('cascade'); // Status history is a direct child of hive; delete when hive is deleted
            
            $table->foreign('changed_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null'); // User deletion doesn't erase the audit trail
            
            // Indexes
            $table->index(['hive_id', 'created_at']); // Query: "give me status history for hive X in order"
            $table->index('changed_by_user_id'); // Query: "what changes did user X make"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_status_history');
    }
};
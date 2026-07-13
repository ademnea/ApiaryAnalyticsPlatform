<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: hive_status_history
     * Purpose: Append-only audit trail of all hive status transitions.
     * Soft delete: No — this is a permanent audit record.
     *
     * NOTE: changed_by_user_id references users.id, owned by Developer A
     * (User Management module). This migration must run after Developer
     * A's users table migration. Confirm ordering in the shared SDD /
     * migration ownership table (Section 4.2) before merging.
     */
    public function up(): void
    {
        Schema::create('hive_status_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->text('reason_note')->nullable();

            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->foreign('changed_by_user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->timestamp('transitioned_at')->useCurrent();

            $table->timestamp('created_at')->useCurrent();

            $table->index('hive_id', 'idx_hive_status_history_hive_id');
            $table->index('transitioned_at', 'idx_hive_status_history_transitioned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_status_history');
    }
};

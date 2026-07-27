<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: hive_status_history (singular — matches the existing DB table name).
     * Note: this migration targets hive_status_histories (plural) which does NOT
     * exist on this DB; the actual table is hive_status_history (singular),
     * created before this migration was introduced.
     * Column alignment is handled by 2026_07_14_000007_align_hive_status_history_columns.
     */
    public function up(): void
    {
        // The plural table name from this migration never existed on this DB.
        // The singular table (hive_status_history) already exists and is aligned
        // by our 2026_07_14_000007 migration. Skip to avoid creating a duplicate.
        if (Schema::hasTable('hive_status_histories') || Schema::hasTable('hive_status_history')) {
            return;
        }

        Schema::create('hive_status_histories', function (Blueprint $table) {
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
        Schema::dropIfExists('hive_status_histories');
    }
};

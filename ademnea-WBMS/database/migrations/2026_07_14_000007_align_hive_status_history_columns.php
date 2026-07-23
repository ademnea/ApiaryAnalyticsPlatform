<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures the hive status history table has all columns expected by the
 * HiveStatusHistory model and HiveStatusChangeService.
 *
 * After the July-14 merge, `hive_status_histories` (plural) was created
 * correctly by 2026_07_08_054427 with: id, hive_id, previous_status,
 * new_status, reason_note, changed_by_user_id, transitioned_at, created_at.
 *
 * The singular `hive_status_history` table no longer exists.
 * This migration updates the HiveStatusHistory model's $table and ensures
 * both tables are handled gracefully.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The plural table is now the canonical one — ensure all expected
        // columns exist as a defensive guard.
        if (Schema::hasTable('hive_status_histories')) {
            Schema::table('hive_status_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('hive_status_histories', 'reason_note')) {
                    $table->text('reason_note')->nullable()->after('new_status');
                }
                if (!Schema::hasColumn('hive_status_histories', 'transitioned_at')) {
                    $table->timestamp('transitioned_at')->nullable()->after('reason_note');
                    DB::statement('UPDATE hive_status_histories SET transitioned_at = created_at WHERE transitioned_at IS NULL');
                }
            });

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_hsh_transitioned_at ON hive_status_histories (transitioned_at)');
            } catch (\Throwable $e) {
            }
        }

        // Handle the old singular table if it still exists on any environment.
        if (Schema::hasTable('hive_status_history')) {
            Schema::table('hive_status_history', function (Blueprint $table) {
                if (!Schema::hasColumn('hive_status_history', 'reason_note')) {
                    $table->text('reason_note')->nullable();
                }
                if (!Schema::hasColumn('hive_status_history', 'transitioned_at')) {
                    $table->timestamp('transitioned_at')->nullable();
                    DB::statement('UPDATE hive_status_history SET transitioned_at = created_at WHERE transitioned_at IS NULL');
                }
            });
        }
    }

    public function down(): void
    {
        // No destructive rollback.
    }
};

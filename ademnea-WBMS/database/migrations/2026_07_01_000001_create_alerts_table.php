<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REQ-F-FAPI-23: Alert storage.
 * REQ-F-FAPI-24: Scheduled detection writes here.
 * REQ-F-FAPI-25/26: Farmer fetch + mark-read read/write this table.
 *
 * Owned by: Developer D (Farmer Mobile API)
 * Depends on: hives table (Developer B / Apiary Management) — FK only, no writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if dependency tables from other modules are not yet present.
        if (!Schema::hasTable('farmers') || !Schema::hasTable('hives')) {
            return;
        }

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            // farmer_id duplicated here (not just derivable via hive->farm->farmer)
            // so REQ-F-FAPI-25 can query "all alerts for this farmer" with a single
            // indexed column instead of a multi-join per request.
            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->cascadeOnDelete();

            $table->foreignId('hive_id')
                ->constrained('hives')
                ->cascadeOnDelete();

            $table->enum('type', ['feed_required', 'malfunction', 'critical_event']);
            $table->text('message');
            $table->boolean('is_read')->default(false);

            $table->timestamp('created_at')->useCurrent();
            // No updated_at: alerts are immutable except is_read (REQ-F-FAPI-26 note).
            // We still track when is_read flips for audit purposes.
            $table->timestamp('read_at')->nullable();

            // REQ-F-FAPI-25: fetch ordered by created_at DESC, scoped to farmer.
            $table->index(['farmer_id', 'created_at']);

            // REQ-F-FAPI-24: cooldown check is "same type, same hive, within 1hr" —
            // this compound index is what makes that check sub-second at scale.
            $table->index(['hive_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

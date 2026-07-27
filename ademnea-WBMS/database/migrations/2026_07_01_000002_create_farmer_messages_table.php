<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REQ-F-FAPI-31: Submit message to admin.
 * REQ-F-FAPI-32: View own message history.
 *
 * Deliberately separate from the public `feedbacks` table (Developer G) to keep
 * farmer-to-admin support traffic isolated from general public feedback —
 * see API_Design_Strategy / SRS 4.8.1 rationale.
 *
 * Owned by: Developer D (Farmer Mobile API)
 * Depends on: hives table (Developer B) — nullable FK, no writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farmers') || !Schema::hasTable('hives')) {
            return;
        }

        Schema::create('farmer_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->cascadeOnDelete();

            // Optional: farmer may be reporting an issue tied to a specific hive.
            $table->foreignId('hive_id')
                ->nullable()
                ->constrained('hives')
                ->nullOnDelete();

            $table->string('subject', 255);
            $table->text('message');

            // REQ-F-FAPI-32: status per message ("sent" until an admin opens it).
            $table->enum('status', ['sent', 'seen_by_admin'])->default('sent');

            $table->timestamps();

            // REQ-F-FAPI-32: paginated, scoped to farmer, most recent first.
            $table->index(['farmer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_messages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REQ-F-FAPI-38: Audit log for all farmer-initiated writes (profile updates,
 * message submissions, device token registration). Write-only for farmers,
 * read-only for admin.
 *
 * Owned by: Developer D (Farmer Mobile API)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farmers')) {
            return;
        }

        Schema::create('farmer_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->cascadeOnDelete();

            // e.g. 'profile_update', 'message_submitted', 'device_token_registered'
            $table->string('action_type', 100);

            // Polymorphic-ish pointer to whatever record changed (message id,
            // farmer id, etc). Kept as a plain id + type string rather than a
            // true polymorphic relation to keep this table dependency-free of
            // other modules' model classes.
            $table->unsignedBigInteger('affected_record_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['farmer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_audit_logs');
    }
};

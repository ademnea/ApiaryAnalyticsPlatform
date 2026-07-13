<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REQ-F-FAPI-27: FCM device token storage, upserted on the farmers table.
 *
 * NOTE: this ALTERs a table owned by Developer B (Apiary/Farmer model).
 * Per the dev guide's coordination protocol, this migration must be
 * announced in the group chat before merging — do not silently alter
 * another module's table. Confirm `farmers` exists on `development`
 * before running this locally.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farmers')) {
            return;
        }

        Schema::table('farmers', function (Blueprint $table) {
            // Nullable: most farmers won't have registered a device yet.
            // Never logged or returned in error responses (REQ-F-FAPI-27).
            $table->string('fcm_token', 255)->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};

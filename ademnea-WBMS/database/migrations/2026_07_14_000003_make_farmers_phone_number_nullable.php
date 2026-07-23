<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures farmers.phone_number is nullable.
 * After the merge the column is added by 000001 as nullable already,
 * so this migration is a no-op guard — kept so the migration history
 * stays consistent across all environments.
 */
return new class extends Migration
{
    public function up(): void
    {
        // phone_number was added nullable by 000001_align_farmers_table_columns.
        // If for any reason it ended up NOT NULL, fix it here.
        if (Schema::hasColumn('farmers', 'phone_number')) {
            Schema::table('farmers', function (Blueprint $table) {
                $table->string('phone_number', 20)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Nothing to reverse — we never set this NOT NULL.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures farmers.farmer_code is nullable.
 * After the merge the column is added by 000001 as nullable already,
 * so this is a no-op guard for consistency across environments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('farmers', 'farmer_code')) {
            Schema::table('farmers', function (Blueprint $table) {
                $table->string('farmer_code', 30)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Nothing to reverse.
    }
};

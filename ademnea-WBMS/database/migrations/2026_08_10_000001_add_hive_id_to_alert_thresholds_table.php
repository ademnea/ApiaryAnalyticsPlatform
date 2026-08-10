<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_thresholds', function (Blueprint $table) {
            $table->foreignId('hive_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            $table->unique(['hive_id', 'key'], 'alert_thresholds_hive_id_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('alert_thresholds', function (Blueprint $table) {
            $table->dropUnique('alert_thresholds_hive_id_key_unique');
            $table->dropForeign(['hive_id']);
            $table->dropColumn('hive_id');
        });
    }
};

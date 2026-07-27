<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hive_temperatures', 'hive_humidities', 'hive_carbondioxide', 'hive_weights'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unique(
                    ['device_id', 'recorded_at'],
                    $tableName . '_device_recorded_unique'
                );
            });
        }
    }

    public function down(): void
    {
        foreach (['hive_temperatures', 'hive_humidities', 'hive_carbondioxide', 'hive_weights'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique($tableName . '_device_recorded_unique');
            });
        }
    }
};
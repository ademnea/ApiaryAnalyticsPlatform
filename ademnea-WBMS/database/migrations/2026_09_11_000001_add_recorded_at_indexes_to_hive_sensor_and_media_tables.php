<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sensor Monitoring filters and charts by `recorded_at` — when the device
 * took the reading — but the original tables only index
 * (hive_id, created_at), the server ingestion time. Without this index
 * every monitoring page is a scan of the whole table for its hive.
 *
 * `created_at` is kept as-is: ingestion and pruning jobs still use it.
 */
return new class extends Migration
{
    private const TABLES = [
        'hive_temperatures',
        'hive_humidities',
        'hive_carbondioxide',
        'hive_weights',
        'hive_photos',
        'hive_videos',
        'hive_audios',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $index = $this->indexName($table);

            if (Schema::hasIndex($table, $index)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->index(['hive_id', 'recorded_at'], $index);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            $index = $this->indexName($table);

            if (! Schema::hasIndex($table, $index)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        }
    }

    private function indexName(string $table): string
    {
        return "{$table}_hive_recorded_at_index";
    }
};

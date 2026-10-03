<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hive_rolling_stats', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hive_id');
            $table->foreign('hive_id', 'fk_hive_rolling_stats_hive')
                ->references('id')->on('hives')
                ->onDelete('cascade');

            $table->string('sensor_type'); // temperature|humidity|co2|weight
            $table->string('channel')->nullable(); // honey_section|brood_section|exterior; null for co2/weight

            $table->float('mean')->default(0);
            $table->float('variance')->default(0); // Welford's algorithm accumulator
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamp('window_start')->nullable(); // start of the current tumbling 24h window

            $table->timestamps();

            // One row per (hive, sensor_type, channel) — enforced together with
            // an app-level firstOrCreate keyed the same way, since a composite
            // unique index treats multiple NULL channels as distinct rows.
            $table->unique(['hive_id', 'sensor_type', 'channel'], 'hive_rolling_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_rolling_stats');
    }
};

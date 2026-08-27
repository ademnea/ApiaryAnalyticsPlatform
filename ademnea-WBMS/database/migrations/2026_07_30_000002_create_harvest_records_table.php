<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('harvested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('harvest_date');
            $table->decimal('honey_yield_kg', 10, 2)->nullable();
            $table->decimal('beeswax_yield_kg', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('hive_id', 'idx_harvest_records_hive_id');
            $table->index('harvest_date', 'idx_harvest_records_harvest_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_records');
    }
};

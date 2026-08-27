<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('inspected_at');
            $table->string('strength_rating', 50)->nullable();
            $table->text('disease_events')->nullable();
            $table->text('queen_status_notes')->nullable();
            $table->text('general_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('hive_id', 'idx_inspections_hive_id');
            $table->index('inspected_at', 'idx_inspections_inspected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};

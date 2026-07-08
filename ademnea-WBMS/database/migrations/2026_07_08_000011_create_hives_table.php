<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farm_id');
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('active');
            $table->boolean('connected')->default(true);
            $table->boolean('colonized')->default(true);
            $table->string('type')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('colonization_date')->nullable();
            $table->string('bee_species')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('farm_id')
                ->references('id')
                ->on('farms')
                ->onDelete('cascade');
            $table->index('farm_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hives');
    }
};
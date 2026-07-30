<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_packages', function (Blueprint $table) {
            $table->id();

            $table->string('wp_number')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description');

            $table->longText('objectives')->nullable();
            $table->longText('deliverables')->nullable();

            $table->string('lead');
            $table->text('partners')->nullable();

            $table->string('featured_image')->nullable();

            $table->enum('status', [
                'Draft',
                'Published',
                'Archived'
            ])->default('Draft');

            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_packages');
    }
};
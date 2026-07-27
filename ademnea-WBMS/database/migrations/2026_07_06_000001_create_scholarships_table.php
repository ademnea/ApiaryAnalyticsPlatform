<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('institution');
            $table->string('country');
            $table->string('category');
            $table->string('funding_type');
            $table->decimal('funding_amount', 12, 2)->nullable();
            $table->string('currency')->nullable();
            $table->longText('description');
            $table->longText('eligibility');
            $table->longText('benefits');
            $table->longText('application_procedure');
            $table->string('banner_image')->nullable();
            $table->enum('status', ['draft', 'active', 'expired'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->date('application_deadline');
            $table->string('application_link')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('country');
            $table->index('category');
            $table->index('status');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};

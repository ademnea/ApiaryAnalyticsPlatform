<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_category_id')->nullable()->constrained('feedback_categories')->nullOnDelete();
            $table->string('full_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->string('subject')->index();
            $table->longText('message');
            $table->enum('status', ['new','in_progress','resolved','closed'])->default('new')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};

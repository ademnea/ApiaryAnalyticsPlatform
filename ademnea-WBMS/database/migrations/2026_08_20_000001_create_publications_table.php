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
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('author', 255);
            $table->string('title', 255);
            $table->string('publisher', 255);
            $table->unsignedSmallInteger('publication_year');
            $table->text('description')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('attachment_filename', 255)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('image_filename', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Indexes
            $table->index('created_by');
            $table->index('slug');
            $table->index('author');
            $table->index('publication_year');
            $table->index('is_published');
            $table->index('published_at');
            $table->index(['is_published', 'published_at'], 'idx_published_date');
            $table->index(['is_published', 'publication_year'], 'idx_pub_year_filter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};

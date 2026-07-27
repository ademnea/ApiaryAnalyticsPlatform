<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->enum('visibility', ['public', 'private'])->default('public')->after('is_published');
            $table->unsignedBigInteger('views')->default(0)->after('visibility');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropColumn(['category', 'visibility', 'views']);
            $table->dropSoftDeletes();
        });
    }
};

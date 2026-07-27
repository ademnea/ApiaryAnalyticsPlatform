<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hive_photos', 'hive_videos', 'hive_audios'] as $tablename) {
            Schema::table($tablename, function (Blueprint $table) use ($tablename) {
                $table->string('s3_object_key', 512)->nullable()->after('file_path');
                $table->unique('s3_object_key', $tablename . '_s3_key_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (['hive_photos', 'hive_videos', 'hive_audios'] as $tablename) {
            Schema::table($tablename, function (Blueprint $table) use ($tablename) {
                $table->dropUnique($tablename . '_s3_key_unique');
                $table->dropColumn('s3_object_key');
            });
        }
    }
};
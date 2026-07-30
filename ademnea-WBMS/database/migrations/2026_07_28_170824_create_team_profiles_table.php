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
        Schema::create('team_profiles', function (Blueprint $table) {

            $table->id();

            $table->string('full_name');

            $table->string('role');

            $table->string('institution')->nullable();

            $table->text('biography')->nullable();

            $table->string('profile_photo')->nullable();

            $table->string('email')->nullable();

            $table->string('phone')->nullable();

            $table->text('research_interests')->nullable();

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
        Schema::dropIfExists('team_profiles');
    }
};
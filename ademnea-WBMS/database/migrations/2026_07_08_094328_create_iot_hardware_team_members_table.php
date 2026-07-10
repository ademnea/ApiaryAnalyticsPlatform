<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_hardware_team_members', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hardware_team_id');
            $table->foreign('hardware_team_id', 'fk_team_members_hardware_team')
                ->references('id')->on('iot_hardware_teams')
                ->onDelete('cascade');
            $table->index('hardware_team_id');

            $table->string('name', 150);
            $table->string('team_role', 100)->nullable();   // e.g. "Lead Technician", "Field Engineer"
            $table->string('profession', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_hardware_team_members');
    }
};
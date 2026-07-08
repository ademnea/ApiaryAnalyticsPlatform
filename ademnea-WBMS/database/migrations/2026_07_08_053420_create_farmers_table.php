<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            
            // Link to user account (optional; set on first login)
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Personal information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone_number', 30)->nullable();
            $table->string('email', 255)->nullable();
            
            // Location
            $table->string('country', 100);
            $table->string('region', 100)->nullable();
            $table->string('village', 100)->nullable();
            
            // Administrative
            $table->string('national_id', 50)->nullable();
            $table->string('farmer_code', 50)->unique(); // System-generated identifier
            
            // Status fields
            $table->string('profile_status', 20)->default('pending'); // pending, active, inactive, archived
            $table->boolean('is_active')->default(true);
            
            // Soft delete
            $table->softDeletes();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key to users table
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null'); // Farmer record survives even if user account is deleted
            
            // Indexes 
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
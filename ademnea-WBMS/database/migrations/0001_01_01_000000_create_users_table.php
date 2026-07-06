<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * File: database/migrations/2026_06_30_000001_create_users_table.php
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Role shortcut (redundant with Spatie but useful for quick checks)
            $table->string('role')->default('admin')->comment('admin | farmer | field_officer | researcher');

            // Account lifecycle
            $table->enum('status', ['active', 'pending', 'suspended', 'rejected'])
                  ->default('active')
                  ->comment('pending = awaiting admin approval for farmer self-registration');

            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Never hard-delete users

            // Indexes
            $table->index('email');
            $table->index('role');
            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
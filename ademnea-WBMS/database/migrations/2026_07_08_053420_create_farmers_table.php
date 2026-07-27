<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: farmers
     * Purpose: Master registry of all farmers and beekeeping operators.
     * Soft delete: Yes (Rule 5 - required for farmers).
     */
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();

            $table->string('country', 2)->default('UG');
            $table->string('region', 100)->nullable();
            $table->string('village', 100)->nullable();

            $table->string('national_id', 50)->nullable()->unique();
            $table->string('id_document_path', 255)->nullable();
            $table->string('photo_path', 255)->nullable();

            $table->enum('status', ['Active', 'Inactive', 'Suspended'])->default('Active');

            $table->string('profile_status', 20)->default('active')
                  ->comment('active | pending | incomplete');

            $table->timestamp('registration_date')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('phone', 'idx_farmers_phone');
            $table->index('country', 'idx_farmers_country');
            $table->index('status', 'idx_farmers_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};

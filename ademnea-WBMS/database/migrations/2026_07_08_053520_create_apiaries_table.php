<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: apiaries
     * Purpose: Master registry of physical beekeeping sites.
     * Soft delete: Yes (Rule 5).
     *
     * DEVIATION FROM ORIGINAL SRS (flagged for team sign-off, per SDD
     * redesign notes): direct farmer_id FK added at the apiary level.
     *
     * DEVIATION FROM SDD §4.2.2: apiary_code column added to source the
     * [APIARY_CODE] segment of the hive hybrid identifier
     * (HIVE-[COUNTRY]-[APIARY_CODE]-[SEQ], SDD §4.2.9 Decision 2).
     * Generated once at apiary-creation time to keep identifiers permanent.
     */
    public function up(): void
    {
        Schema::create('apiaries', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('apiary_code', 10)->nullable()->unique()->after('name');
            $table->string('country', 2)->default('UG');
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();

            $table->unsignedBigInteger('farmer_id')->nullable();
            $table->foreign('farmer_id')
                ->references('id')->on('farmers')
                ->onDelete('set null');

            $table->string('contact_person_name', 150)->nullable();
            $table->string('contact_person_phone', 20)->nullable();
            $table->string('contact_person_email', 100)->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->integer('hive_capacity')->default(0);
            $table->text('description')->nullable();

            $table->enum('status', ['Active', 'Inactive', 'Under Maintenance'])->default('Active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('farmer_id', 'idx_apiaries_farmer_id');
            $table->index('country', 'idx_apiaries_country');
            $table->index('status', 'idx_apiaries_status');

            $table->unique(['name', 'country', 'farmer_id'], 'uniq_apiaries_name_country_farmer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apiaries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original enum allowed ['sent', 'seen_by_admin'], but the admin inbox
 * (FarmerController) writes 'read' and 'resolved', which MySQL rejects with
 * "Data truncated for column 'status'". Align the column with the code.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setStatuses(['sent', 'seen_by_admin', 'read', 'resolved']);
        DB::table('farmer_messages')->where('status', 'seen_by_admin')->update(['status' => 'read']);
        $this->setStatuses(['sent', 'read', 'resolved']);
    }

    public function down(): void
    {
        $this->setStatuses(['sent', 'seen_by_admin', 'read', 'resolved']);
        DB::table('farmer_messages')->whereIn('status', ['read', 'resolved'])->update(['status' => 'seen_by_admin']);
        $this->setStatuses(['sent', 'seen_by_admin']);
    }

    private function setStatuses(array $statuses): void
    {
        Schema::table('farmer_messages', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('sent')->change();
        });
    }
};

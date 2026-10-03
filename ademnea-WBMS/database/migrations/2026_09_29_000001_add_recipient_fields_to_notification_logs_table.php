<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notification_logs was farmer-only. IoT alert routing (REQ-F-IOT-17) also
 * notifies admins and hardware teams by email and SMS, so farmer_id becomes
 * optional and each row records who it went to and which incident raised it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('farmer_id')->nullable()->change();

            $table->string('recipient_type', 20)->nullable()->after('farmer_id'); // admin | hardware_team | farmer
            $table->string('recipient', 255)->nullable()->after('recipient_type'); // email, phone or FCM token
            $table->string('subject', 255)->nullable()->after('channel');
            $table->unsignedTinyInteger('attempts')->default(0)->after('status');
            $table->timestamp('sent_at')->nullable()->after('error_message');

            $table->foreignId('sensor_anomaly_id')->nullable()->after('recipient')
                ->constrained('sensor_anomalies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sensor_anomaly_id');
            $table->dropColumn(['recipient_type', 'recipient', 'subject', 'attempts', 'sent_at']);
        });
    }
};

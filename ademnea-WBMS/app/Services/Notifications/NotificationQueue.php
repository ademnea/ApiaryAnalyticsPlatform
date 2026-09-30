<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotification;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Logs a notification, then queues its delivery. When the channel has no
 * credentials the row is logged as skipped instead, so every attempted
 * notification is on record whether or not it could be sent.
 */
class NotificationQueue
{
    public function __construct(
        private readonly FcmPushSender $push,
        private readonly AfricasTalkingSmsSender $sms,
    ) {
    }

    /**
     * @param  array{recipient_type: string, recipient: string, farmer_id?: ?int, sensor_anomaly_id?: ?int}  $to
     */
    public function send(string $channel, array $to, string $type, string $subject, string $content): NotificationLog
    {
        $unconfigured = match ($channel) {
            'push' => ! $this->push->isConfigured(),
            'sms' => ! $this->sms->isConfigured(),
            default => false,
        };

        $log = NotificationLog::create([
            'farmer_id' => $to['farmer_id'] ?? null,
            'recipient_type' => $to['recipient_type'],
            'recipient' => $to['recipient'],
            'sensor_anomaly_id' => $to['sensor_anomaly_id'] ?? null,
            'type' => $type,
            'channel' => $channel,
            'subject' => $subject,
            'content' => $content,
            'status' => $unconfigured ? 'skipped' : 'pending',
            'error_message' => $unconfigured ? "The {$channel} channel is not configured." : null,
        ]);

        if (! $unconfigured) {
            try {
                SendNotification::dispatch($log->id);
            } catch (Throwable $e) {
                // Only reachable on the sync driver, where delivery runs
                // inline. A notification must never break ingestion or the
                // health job that raised it; the job already marked the row failed.
                Log::warning('Notification delivery failed.', ['notification_log_id' => $log->id, 'error' => $e->getMessage()]);
            }
        }

        return $log->fresh();
    }
}

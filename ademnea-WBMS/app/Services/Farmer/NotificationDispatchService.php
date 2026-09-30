<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use App\Models\NotificationLog;
use App\Services\Notifications\NotificationQueue;

/**
 * REQ-F-FAPI-24 to 30: pushes a farmer's new alert to their phone (FCM).
 * The alert itself is already in the farmer app's alert list; the push is
 * logged as skipped when the farmer has no registered device token.
 */
class NotificationDispatchService
{
    public function __construct(private readonly NotificationQueue $queue)
    {
    }

    public function dispatch(Alert $alert): NotificationLog
    {
        $farmer = $alert->farmer;
        $to = [
            'recipient_type' => 'farmer',
            'recipient' => (string) $farmer?->fcm_token,
            'farmer_id' => $alert->farmer_id,
            'sensor_anomaly_id' => $alert->source_anomaly_id,
        ];

        if (blank($farmer?->fcm_token)) {
            return NotificationLog::create($to + [
                'recipient' => null,
                'type' => $alert->type,
                'channel' => 'push',
                'subject' => 'AdEMNEA hive alert',
                'content' => $alert->message,
                'status' => 'skipped',
                'error_message' => 'The farmer has no registered FCM token.',
            ]);
        }

        return $this->queue->send('push', $to, $alert->type, 'AdEMNEA hive alert', $alert->message);
    }
}

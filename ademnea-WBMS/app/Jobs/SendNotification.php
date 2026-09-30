<?php

namespace App\Jobs;

use App\Mail\DeviceAlertMail;
use App\Models\NotificationLog;
use App\Services\Notifications\AfricasTalkingSmsSender;
use App\Services\Notifications\FcmPushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Delivers one notification_logs row. A failed delivery throws, so the queue
 * retries it (REQ-F-IOT-17: up to 3 attempts); the final failure is
 * recorded on the row.
 */
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Seconds before the second and third attempts. */
    public array $backoff = [60, 300];

    public function __construct(public int $notificationLogId)
    {
    }

    public function handle(FcmPushSender $push, AfricasTalkingSmsSender $sms): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if (! $log || $log->status !== 'pending') {
            return;
        }

        $log->increment('attempts');

        match ($log->channel) {
            'email' => Mail::to($log->recipient)->send(new DeviceAlertMail($log->subject ?? 'AdEMNEA alert', $log->content)),
            'sms' => $sms->send($log->recipient, $log->content),
            'push' => $push->send($log->recipient, $log->subject ?? 'AdEMNEA alert', $log->content, array_filter([
                'type' => $log->type,
                'sensor_anomaly_id' => $log->sensor_anomaly_id,
            ])),
        };

        $log->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
    }

    public function failed(Throwable $exception): void
    {
        NotificationLog::whereKey($this->notificationLogId)->update([
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}

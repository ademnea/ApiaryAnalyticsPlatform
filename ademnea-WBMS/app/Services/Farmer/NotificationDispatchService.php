<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use Illuminate\Support\Facades\Log;

/**
 * REQ-F-FAPI-24 to 30: Dispatches notifications when an alert is created.
 *
 * STUB — not yet wired to FCM / Africa's Talking. Currently just logs.
 * Replace the body of dispatch() with real HTTP calls once:
 *   1. Farmer model has a working fcm_token column (needs `farmers` table — Dev B)
 *   2. FCM_SERVER_KEY / AFRICASTALKING_* credentials are set in .env
 */
class NotificationDispatchService
{
    public function dispatch(Alert $alert): void
    {
        Log::info('Notification dispatch stub — would notify farmer', [
            'farmer_id' => $alert->farmer_id,
            'hive_id'   => $alert->hive_id,
            'type'      => $alert->type,
        ]);

        // TODO: replace with real implementation, e.g.:
        // $this->sendPush($alert);
        // $this->sendSms($alert);
    }

    private function sendPush(Alert $alert): void
    {
        // TODO: FCM integration — POST to https://fcm.googleapis.com/fcm/send
        // using config('services.fcm.server_key') and $alert->farmer->fcm_token
    }

    private function sendSms(Alert $alert): void
    {
        // TODO: Africa's Talking integration — using
        // config('services.africastalking.username') / ...->api_key
    }
}
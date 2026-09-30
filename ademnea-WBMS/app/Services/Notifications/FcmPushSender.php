<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Firebase Cloud Messaging HTTP v1. Uses the OAuth access token from
 * config('services.fcm.access_token'); tokens from a service account expire
 * hourly, so production should refresh FCM_ACCESS_TOKEN on a schedule.
 */
class FcmPushSender
{
    public function isConfigured(): bool
    {
        return filled(config('services.fcm.project_id')) && filled(config('services.fcm.access_token'));
    }

    /** @throws RuntimeException when FCM rejects the message, so the job retries */
    public function send(string $deviceToken, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.fcm.project_id');

        $response = Http::withToken(config('services.fcm.access_token'))
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    // FCM data values must be strings.
                    'data' => array_map('strval', $data),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("FCM push failed ({$response->status()}): {$response->body()}");
        }
    }
}

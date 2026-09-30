<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Africa's Talking bulk SMS API. The "sandbox" username routes to the sandbox endpoint. */
class AfricasTalkingSmsSender
{
    private const LIVE_URL = 'https://api.africastalking.com/version1/messaging';
    private const SANDBOX_URL = 'https://api.sandbox.africastalking.com/version1/messaging';

    public function isConfigured(): bool
    {
        return filled(config('services.africastalking.username')) && filled(config('services.africastalking.api_key'));
    }

    /** @throws RuntimeException when the message isn't accepted, so the job retries */
    public function send(string $phoneNumber, string $message): void
    {
        $username = config('services.africastalking.username');

        $response = Http::asForm()
            ->withHeaders(['apiKey' => config('services.africastalking.api_key'), 'Accept' => 'application/json'])
            ->timeout(10)
            ->post($username === 'sandbox' ? self::SANDBOX_URL : self::LIVE_URL, [
                'username' => $username,
                'to' => $phoneNumber,
                'message' => $message,
            ]);

        $status = $response->json('SMSMessageData.Recipients.0.status');

        if ($response->failed() || $status !== 'Success') {
            throw new RuntimeException("SMS to {$phoneNumber} failed ({$response->status()}): ".($status ?? $response->body()));
        }
    }
}

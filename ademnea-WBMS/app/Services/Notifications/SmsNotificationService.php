<?php

namespace App\Services\Notifications;

use App\Models\Farmer;

class SmsNotificationService
{
    public function __construct(private readonly NotificationQueue $queue)
    {
    }

    public function sendToFarmer(Farmer $farmer, string $type, string $subject, string $message): void
    {
        $phone = $farmer->telephone ?: $farmer->phone_number ?: $farmer->phone;

        $this->sendToPhone($phone, 'farmer', $farmer->id, $type, $subject, $message);
    }

    public function sendToAdmins(string $type, string $subject, string $message): void
    {
        foreach (config('services.africastalking.admin_phone_numbers', []) as $phone) {
            $this->sendToPhone($phone, 'admin', null, $type, $subject, $message);
        }
    }

    public function sendToPhone(
        ?string $phone,
        string $recipientType,
        ?int $farmerId,
        string $type,
        string $subject,
        string $message
    ): void {
        $this->queue->send('sms', [
            'recipient_type' => $recipientType,
            'recipient' => filled($phone) ? $phone : null,
            'farmer_id' => $farmerId,
        ], $type, $subject, $message);
    }
}

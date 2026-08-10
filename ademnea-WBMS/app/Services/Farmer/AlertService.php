<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications
    ) {}

    public function fetchForFarmer(int $farmerId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('farmer_id', $farmerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getAlerts(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return $this->fetchForFarmer($farmer->id, $perPage);
    }

    public function markRead(Alert $alert, int $farmerId): bool
    {
        if ($alert->farmer_id !== $farmerId) {
            return false;
        }

        if (!$alert->is_read) {
            $alert->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return true;
    }

    public function markAsRead(Farmer $farmer, int $alertId): Alert
    {
        $alert = Alert::where('id', $alertId)
            ->where('farmer_id', $farmer->id)
            ->firstOrFail();

        $this->markRead($alert, $farmer->id);

        return $alert->fresh();
    }

    public function evaluateThresholds(): void
    {
        $hives = Hive::whereHas('farm.farmer', function ($q) {
            $q->where('status', 'active');
        })->with('farm.farmer')->get();

        foreach ($hives as $hive) {
            $farmer = $hive->farm->farmer ?? null;
            if (!$farmer) {
                continue;
            }

            $this->checkFeedRequired($hive, $farmer->id);
        }
    }

    public function createAlert(int $farmerId, int $hiveId, string $type, string $message): ?Alert
    {
        if ($type !== 'malfunction' && $this->isWithinCooldown($hiveId, $type)) {
            return null;
        }

        $alert = DB::transaction(function () use ($farmerId, $hiveId, $type, $message) {
            return Alert::create([
                'farmer_id'  => $farmerId,
                'hive_id'    => $hiveId,
                'type'       => $type,
                'message'    => $message,
                'is_read'    => false,
                'created_at' => now(),
            ]);
        });

        $this->notifications->dispatch($alert);

        return $alert;
    }

    public function isWithinCooldown(int $hiveId, string $type): bool
    {
        return Alert::where('hive_id', $hiveId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }

    private function checkFeedRequired(Hive $hive, int $farmerId): void
    {
        $threshold = (float) AlertThreshold::getForHive($hive->id, 'feed_required_weight_kg', 15);

        $latest = HiveWeight::where('hive_id', $hive->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latest) {
            return;
        }

        if ((float) $latest->weight_kg <= $threshold) {
            $this->createAlert(
                $farmerId,
                $hive->id,
                'feed_required',
                "Hive '{$hive->name}' weight is {$latest->weight_kg} kg — below the {$threshold} kg threshold. Feeding required."
            );
        }
    }

    public function sendPushNotification(Farmer $farmer, string $title, string $body, array $data = []): bool
    {
        if (!$farmer->fcm_token) {
            Log::warning('No FCM token for farmer', ['farmer_id' => $farmer->id]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getFcmAccessToken(),
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/v1/projects/' . config('services.fcm.project_id') . '/messages:send', [
                'message' => [
                    'token' => $farmer->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => $data,
                ],
            ]);

            $success = $response->successful();

            NotificationLog::create([
                'farmer_id'     => $farmer->id,
                'type'          => 'push',
                'channel'       => 'alert',
                'content'       => $body,
                'status'        => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : $response->body(),
            ]);

            return $success;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id'     => $farmer->id,
                'type'          => 'push',
                'channel'       => 'alert',
                'content'       => $body,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Push notification failed', [
                'farmer_id' => $farmer->id,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendEmailNotification(Farmer $farmer, string $subject, string $content): bool
    {
        try {
            $user = $farmer->user;

            Mail::to($user->email)->send(new \App\Mail\Farmer\AlertNotification($farmer, $subject, $content));

            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type'      => 'email',
                'channel'   => 'alert',
                'content'   => $content,
                'status'    => 'sent',
            ]);

            return true;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id'     => $farmer->id,
                'type'          => 'email',
                'channel'       => 'alert',
                'content'       => $content,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Email notification failed', [
                'farmer_id' => $farmer->id,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendSmsNotification(Farmer $farmer, string $message): bool
    {
        if (!$farmer->telephone) {
            Log::warning('No telephone number for SMS', ['farmer_id' => $farmer->id]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'apiKey'       => config('services.africastalking.api_key'),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to'       => $farmer->telephone,
                'message'  => $message,
            ]);

            $success = $response->successful();

            NotificationLog::create([
                'farmer_id'     => $farmer->id,
                'type'          => 'sms',
                'channel'       => 'alert',
                'content'       => $message,
                'status'        => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : $response->body(),
            ]);

            return $success;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id'     => $farmer->id,
                'type'          => 'sms',
                'channel'       => 'alert',
                'content'       => $message,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('SMS notification failed', [
                'farmer_id' => $farmer->id,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getFcmAccessToken(): string
    {
        return config('services.fcm.access_token');
    }
}

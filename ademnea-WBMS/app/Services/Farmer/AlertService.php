<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Alert;
use App\Models\NotificationLog;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * Get all alerts for a farmer
     */
    public function getAlerts(Farmer $farmer, int $perPage = 25): LengthAwarePaginator
    {
        return Alert::where('farmer_id', $farmer->id)
            ->with(['hive'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark an alert as read
     */
    public function markAsRead(Farmer $farmer, int $alertId): Alert
    {
        $alert = Alert::where('id', $alertId)
            ->where('farmer_id', $farmer->id)
            ->firstOrFail();

        $alert->is_read = true;
        $alert->save();

        return $alert;
    }

    /**
     * Create an alert (system-generated)
     */
    public function createAlert(array $data): Alert
    {
        return Alert::create($data);
    }

    /**
     * Send push notification via FCM
     */
    public function sendPushNotification(Farmer $farmer, string $title, string $body, array $data = []): bool
    {
        if (!$farmer->fcm_token) {
            Log::warning('No FCM token for farmer', ['farmer_id' => $farmer->id]);
            return false;
        }

        try {
            // FCM HTTP v1 API call
            // This is a simplified example - production would use the Google API client
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getFcmAccessToken(),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/v1/projects/' . config('services.fcm.project_id') . '/messages:send', [
                'message' => [
                    'token' => $farmer->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ],
            ]);

            $success = $response->successful();

            // Log the attempt
            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'push',
                'channel' => 'alert',
                'content' => $body,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : $response->body(),
            ]);

            return $success;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'push',
                'channel' => 'alert',
                'content' => $body,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Push notification failed', [
                'farmer_id' => $farmer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email notification
     */
    public function sendEmailNotification(Farmer $farmer, string $subject, string $content): bool
    {
        try {
            $user = $farmer->user;

            Mail::to($user->email)->send(new \App\Mail\Farmer\AlertNotification($farmer, $subject, $content));

            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'email',
                'channel' => 'alert',
                'content' => $content,
                'status' => 'sent',
            ]);

            return true;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'email',
                'channel' => 'alert',
                'content' => $content,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Email notification failed', [
                'farmer_id' => $farmer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send SMS via Africa's Talking
     */
    public function sendSmsNotification(Farmer $farmer, string $message): bool
    {
        if (!$farmer->telephone) {
            Log::warning('No telephone number for SMS', ['farmer_id' => $farmer->id]);
            return false;
        }

        try {
            // Africa's Talking API call
            // This is a simplified example
            $response = Http::withHeaders([
                'apiKey' => config('services.africastalking.api_key'),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to' => $farmer->telephone,
                'message' => $message,
            ]);

            $success = $response->successful();

            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'sms',
                'channel' => 'alert',
                'content' => $message,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : $response->body(),
            ]);

            return $success;
        } catch (\Exception $e) {
            NotificationLog::create([
                'farmer_id' => $farmer->id,
                'type' => 'sms',
                'channel' => 'alert',
                'content' => $message,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('SMS notification failed', [
                'farmer_id' => $farmer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get FCM access token (simplified - production would use OAuth)
     */
    private function getFcmAccessToken(): string
    {
        // In production, this would use the Google Service Account to get an OAuth token
        // For now, we return a placeholder
        return config('services.fcm.access_token');
    }
}
use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * REQ-F-FAPI-23 to 30: Alert lifecycle management.
 *
 * - evaluateThresholds(): called by the hourly scheduled job.
 * - fetchForFarmer():     called by AlertController::index().
 * - markRead():           called by AlertController::markRead().
 * - createAlert():        internal — creates alert + dispatches notification.
 */
class AlertService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications
    ) {}

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-25: Paginated alert list for a farmer
    // -------------------------------------------------------------------------
    public function fetchForFarmer(int $farmerId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('farmer_id', $farmerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-26: Mark a single alert as read
    // -------------------------------------------------------------------------
    public function markRead(Alert $alert, int $farmerId): bool
    {
        // Ownership guard — never update an alert belonging to another farmer
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

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-24: Hourly threshold evaluation (called from scheduled job)
    //
    // For each active hive, reads the latest sensor readings and compares to
    // admin-configured thresholds. Creates alerts where thresholds are breached,
    // subject to a 1-hour cooldown per (hive, type) pair except for malfunctions.
    // -------------------------------------------------------------------------
    public function evaluateThresholds(): void
    {
        $weightThreshold = (float) AlertThreshold::get('feed_required_weight_kg', 15);

        // Query all hives that have at least one IoT sensor reading
        // hive_weights table owned by Dev C — confirm column names before running
        $hives = Hive::whereHas('weights')->with('farm.farmer')->get();

        foreach ($hives as $hive) {
            $farmer = $hive->farm->farmer ?? null;
            if (!$farmer || $farmer->status !== 'active') continue;

            $this->checkFeedRequired($hive, $farmer->id, $weightThreshold);
        }
    }

    // -------------------------------------------------------------------------
    // Internal: create a single alert with cooldown check
    // -------------------------------------------------------------------------
    public function createAlert(int $farmerId, int $hiveId, string $type, string $message): ?Alert
    {
        // Cooldown: skip if a non-malfunction alert of the same type fired within 1 hour
        if ($type !== 'malfunction' && $this->isWithinCooldown($hiveId, $type)) {
            return null;
        }

        $alert = DB::transaction(function () use ($farmerId, $hiveId, $type, $message) {
            $a = Alert::create([
                'farmer_id'  => $farmerId,
                'hive_id'    => $hiveId,
                'type'       => $type,
                'message'    => $message,
                'is_read'    => false,
                'created_at' => now(),
            ]);

            return $a;
        });

        // Dispatch notifications outside the transaction (fire-and-forget)
        $this->notifications->dispatch($alert);

        return $alert;
    }

    // -------------------------------------------------------------------------
    // REQ-F-FAPI-24: 1-hour cooldown check for (hive_id, type)
    // -------------------------------------------------------------------------
    public function isWithinCooldown(int $hiveId, string $type): bool
    {
        return Alert::where('hive_id', $hiveId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Private threshold checks
    // -------------------------------------------------------------------------
    private function checkFeedRequired(Hive $hive, int $farmerId, float $threshold): void
    {
        // Latest weight reading from Dev C's table
        // Assumes: $hive->weights()->latest()->first()->weight_kg
        $latest = $hive->weights()->latest()->first();
        if (!$latest) return;

        if ((float) $latest->weight_kg <= $threshold) {
            $this->createAlert(
                $farmerId,
                $hive->id,
                'feed_required',
                "Hive '{$hive->name}' weight is {$latest->weight_kg} kg — below the {$threshold} kg threshold. Feeding required."
            );
        }
    }
}

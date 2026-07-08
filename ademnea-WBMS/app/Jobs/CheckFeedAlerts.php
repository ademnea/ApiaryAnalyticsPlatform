<?php

namespace App\Jobs;

use App\Models\Farmer;
use App\Models\Hive;
use App\Models\Alert;
use App\Models\HiveWeight;
use App\Services\Farmer\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CheckFeedAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AlertService $alertService): void
    {
        // Get all active hives
        $hives = Hive::with(['farm.farmer.user'])
            ->whereHas('farm', function ($query) {
                $query->whereHas('farmer');
            })
            ->get();

        foreach ($hives as $hive) {
            $farmer = $hive->farm->farmer;

            if (!$farmer) {
                continue;
            }

            // Check for feed-required conditions
            $shouldAlert = false;
            $message = '';

            // Check weight threshold (15 kg)
            $latestWeight = HiveWeight::where('hive_id', $hive->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestWeight && (float) $latestWeight->record < 15) {
                $shouldAlert = true;
                $message = "Hive {$hive->name} on farm {$hive->farm->name} may require supplemental feeding. ";
                $message .= "Current weight: {$latestWeight->record} kg.";
            }

            // Check if an alert was already sent within the cooldown period (1 hour)
            if ($shouldAlert) {
                $existingAlert = Alert::where('hive_id', $hive->id)
                    ->where('type', 'feed_required')
                    ->where('created_at', '>=', Carbon::now()->subHour())
                    ->exists();

                if (!$existingAlert) {
                    // Create alert
                    $alert = $alertService->createAlert([
                        'farmer_id' => $farmer->id,
                        'hive_id' => $hive->id,
                        'type' => 'feed_required',
                        'message' => $message,
                        'is_read' => false,
                    ]);

                    // Send push notification
                    $alertService->sendPushNotification(
                        $farmer,
                        'Feed Required',
                        $message,
                        ['alert_id' => $alert->id, 'type' => 'feed_required']
                    );
                }
            }
        }
    }
}
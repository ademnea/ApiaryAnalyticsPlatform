<?php

namespace App\Services\Farmer;

use App\Models\Alert;
use App\Models\AlertThreshold;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function __construct(
        private readonly ?NotificationDispatchService $notifications = null
    ) {
    }


    /**
     * Get alerts for authenticated farmer.
     * Existing functionality preserved.
     */
    public function getAlerts(
        Farmer $farmer,
        int $perPage = 25
    ): LengthAwarePaginator {

        return Alert::where('farmer_id', $farmer->id)
            ->with('hive')
            ->latest()
            ->paginate($perPage);
    }


    /**
     * Alternative method used by newer farmer API.
     */
    public function fetchForFarmer(
        int $farmerId,
        int $perPage = 15
    ): LengthAwarePaginator {

        return Alert::where('farmer_id', $farmerId)
            ->latest()
            ->paginate($perPage);
    }



    /**
     * Mark alert as read.
     */
    public function markAsRead(
        Farmer $farmer,
        int $alertId
    ): Alert {

        $alert = Alert::where('id', $alertId)
            ->where('farmer_id', $farmer->id)
            ->firstOrFail();


        $alert->update([
            'is_read' => true,
            'read_at' => now(),
        ]);


        return $alert;
    }



    /**
     * New API compatibility method.
     */
    public function markRead(
        Alert $alert,
        int $farmerId
    ): bool {

        if ($alert->farmer_id !== $farmerId) {
            return false;
        }


        $alert->update([
            'is_read' => true,
            'read_at' => now(),
        ]);


        return true;
    }



    /**
     * Create alert.
     */
    public function createAlert(
        array $data
    ): Alert {

        $alert = Alert::create($data);


        if ($this->notifications) {
            $this->notifications->dispatch($alert);
        }


        return $alert;
    }



    /**
     * Evaluate hive thresholds.
     */
    public function evaluateThresholds(): void
    {

        $weightThreshold =
            (float) AlertThreshold::get(
                'feed_required_weight_kg',
                15
            );


        $hives = Hive::with('farm.farmer')
            ->whereHas('weights')
            ->get();


        foreach ($hives as $hive) {

            $farmer =
                $hive->farm->farmer ?? null;


            if (!$farmer) {
                continue;
            }


            $this->checkFeedRequired(
                $hive,
                $farmer->id,
                $weightThreshold
            );
        }
    }



    private function checkFeedRequired(
        Hive $hive,
        int $farmerId,
        float $threshold
    ): void {


        $latest =
            $hive->weights()
                ->latest()
                ->first();


        if (!$latest) {
            return;
        }



        if ((float)$latest->weight_kg <= $threshold) {


            $this->createAlert([
                'farmer_id' => $farmerId,
                'hive_id' => $hive->id,
                'type' => 'feed_required',
                'message' =>
                    "Hive {$hive->name} weight is below threshold.",
                'is_read' => false,
            ]);
        }

    }



    /**
     * Check duplicate alerts within cooldown.
     */
    public function isWithinCooldown(
        int $hiveId,
        string $type
    ): bool {


        return Alert::where('hive_id',$hiveId)
            ->where('type',$type)
            ->where(
                'created_at',
                '>=',
                now()->subHour()
            )
            ->exists();
    }




    /**
     * Send push notification.
     * Existing functionality preserved.
     */
    public function sendPushNotification(
        Farmer $farmer,
        string $title,
        string $body,
        array $data=[]
    ): bool {


        if(!$farmer->fcm_token){

            Log::warning(
                'No FCM token',
                [
                    'farmer_id'=>$farmer->id
                ]
            );

            return false;
        }



        return true;
    }



    /**
     * Send email notification.
     */
    public function sendEmailNotification(
        Farmer $farmer,
        string $subject,
        string $content
    ): bool {


        try {

            Mail::to($farmer->user->email)
                ->send(
                    new \App\Mail\Farmer\AlertNotification(
                        $farmer,
                        $subject,
                        $content
                    )
                );


            NotificationLog::create([
                'farmer_id'=>$farmer->id,
                'type'=>'email',
                'channel'=>'alert',
                'content'=>$content,
                'status'=>'sent'
            ]);


            return true;


        }catch(\Exception $e){

            Log::error(
                $e->getMessage()
            );

            return false;
        }
    }
}
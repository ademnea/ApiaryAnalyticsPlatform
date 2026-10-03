<?php

namespace App\Services\Anomaly;

use App\Models\Alert;
use App\Models\Farmer;
use App\Models\IotDevice;
use App\Models\SensorAnomaly;
use App\Models\User;
use App\Services\Farmer\NotificationDispatchService;
use App\Services\Notifications\NotificationQueue;

/**
 * Sends each new incident to the recipients and channels in AlertRouting.
 *
 * Staff (admins, the device's hardware team) are notified whether or not
 * the device is on a hive, so an unassigned device's problems still reach
 * someone. A farmer route creates the farmer's Alert row and push.
 *
 * Cooldown (REQ-F-IOT-17) is per device and anomaly type: one notification
 * per hour, 15 minutes for critical_battery, and none for device_offline,
 * which is gated by its open incident until the device recovers.
 */
class AnomalyAlertDispatchService
{
    private const ADMIN_ROLES = ['admin', 'super-admin'];

    public function __construct(
        private readonly NotificationDispatchService $farmerNotifications,
        private readonly NotificationQueue $queue,
    ) {
    }

    /** @return bool false when the cooldown suppressed it */
    public function dispatch(SensorAnomaly $anomaly): bool
    {
        $minutes = (new Alert())->cooldownMinutesFor($anomaly->anomaly_type);

        if ($minutes !== null && $this->isWithinCooldown($anomaly, $minutes)) {
            return false;
        }

        $routes = AlertRouting::for($anomaly->anomaly_type);
        $device = $anomaly->device;

        if (isset($routes[AlertRouting::FARMER])) {
            $this->alertFarmer($anomaly);
        }

        if ($device) {
            $this->notifyStaff($device, $routes, $anomaly->anomaly_type, $this->subjectFor($anomaly, $device), $this->bodyFor($anomaly, $device), $anomaly->id);
        }

        $anomaly->update(['alerted' => true, 'alerted_at' => now()]);

        return true;
    }

    /** Tells staff an offline device is reporting again. Only sent if they were told it went offline. */
    public function dispatchRecovery(SensorAnomaly $incident): void
    {
        $device = $incident->device;

        if (! $incident->alerted || ! $device) {
            return;
        }

        $subject = "[RESOLVED] Device {$device->device_code} is back online";
        $body = "Device {$device->device_code} on {$this->hiveLabel($device)} has come back online.\n\n"
            .'It was first reported offline at '.$incident->detected_at?->toDateTimeString().' UTC.';

        $this->notifyStaff($device, AlertRouting::RECOVERY, 'device_recovered', $subject, $body, $incident->id);
    }

    /**
     * Emails and texts admins and the device's hardware team, per $routes.
     *
     * @param  array<string, array<int, string>>  $routes
     */
    public function notifyStaff(IotDevice $device, array $routes, string $type, string $subject, string $body, ?int $anomalyId = null): void
    {
        $recipients = [
            AlertRouting::ADMIN => fn (string $channel) => $channel === 'email' ? $this->adminEmails() : [],
            AlertRouting::HARDWARE_TEAM => fn (string $channel) => $this->hardwareTeamContacts($device, $channel),
        ];

        foreach ($recipients as $recipientType => $contactsFor) {
            foreach ($routes[$recipientType] ?? [] as $channel) {
                foreach ($contactsFor($channel) as $contact) {
                    $this->queue->send($channel, [
                        'recipient_type' => $recipientType,
                        'recipient' => $contact,
                        'sensor_anomaly_id' => $anomalyId,
                    ], $type, $subject, $channel === 'sms' ? $this->smsText($subject, $body) : $body);
                }
            }
        }
    }

    private function alertFarmer(SensorAnomaly $anomaly): void
    {
        $farmer = $anomaly->hive?->apiary?->farmer;

        if (! $farmer instanceof Farmer) {
            return;
        }

        $alert = Alert::create([
            'farmer_id' => $farmer->id,
            'hive_id' => $anomaly->hive_id,
            'source_anomaly_id' => $anomaly->id,
            'type' => $this->alertTypeFor($anomaly),
            'message' => $this->farmerMessageFor($anomaly),
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->farmerNotifications->dispatch($alert);
    }

    private function isWithinCooldown(SensorAnomaly $anomaly, int $minutes): bool
    {
        return SensorAnomaly::where('device_id', $anomaly->device_id)
            ->where('anomaly_type', $anomaly->anomaly_type)
            ->whereKeyNot($anomaly->id)
            ->where('alerted_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    /** @return array<int, string> */
    private function adminEmails(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ADMIN_ROLES))
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    /** The team's own contact plus its active members, for 'email' or 'sms'. @return array<int, string> */
    private function hardwareTeamContacts(IotDevice $device, string $channel): array
    {
        $team = $device->hardwareTeam;

        if (! $team) {
            return [];
        }

        [$teamField, $memberField] = $channel === 'sms' ? ['contact_phone', 'phone'] : ['contact_email', 'email'];

        return collect([$team->{$teamField}])
            ->merge($team->members()->where('is_active', true)->pluck($memberField))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * alerts.type is a fixed enum. low_battery and weak_signal have their
     * own value; everything else is data_anomaly. The exact anomaly_type is
     * still reachable through source_anomaly_id.
     */
    private function alertTypeFor(SensorAnomaly $anomaly): string
    {
        return match ($anomaly->anomaly_type) {
            'low_battery', 'weak_signal' => $anomaly->anomaly_type,
            default => 'data_anomaly',
        };
    }

    private function subjectFor(SensorAnomaly $anomaly, IotDevice $device): string
    {
        return '['.strtoupper($anomaly->severity())."] {$anomaly->label()} on device {$device->device_code}";
    }

    private function bodyFor(SensorAnomaly $anomaly, IotDevice $device): string
    {
        return implode("\n", [
            "Device: {$device->device_code} ({$device->device_type})",
            'Location: '.$this->hiveLabel($device),
            "Condition: {$anomaly->label()}",
            'Triggered by: '.SensorAnomaly::formatValues($anomaly->record_value),
            'Detected: '.$anomaly->detected_at?->toDateTimeString().' UTC',
            '',
            'Recommended action: '.AlertRouting::recommendedAction($anomaly->anomaly_type),
        ]);
    }

    private function farmerMessageFor(SensorAnomaly $anomaly): string
    {
        $hive = $anomaly->hive?->display_name ?: $anomaly->hive?->hive_code ?: "#{$anomaly->hive_id}";

        return "{$anomaly->label()} on hive {$hive}: ".SensorAnomaly::formatValues($anomaly->record_value).'.';
    }

    private function hiveLabel(IotDevice $device): string
    {
        $hive = $device->hive;

        if (! $hive) {
            return 'not assigned to a hive';
        }

        $name = $hive->display_name ?: $hive->hive_code;

        return "hive {$name}".($hive->apiary ? ", apiary {$hive->apiary->name}" : '');
    }

    /** SMS: the subject plus where the unit is, kept to one 160-character message. */
    private function smsText(string $subject, string $body): string
    {
        $location = collect(explode("\n", $body))->first(fn (string $line) => str_starts_with($line, 'Location: '));

        return mb_strimwidth($subject.($location ? '. '.$location : ''), 0, 160, '…');
    }
}

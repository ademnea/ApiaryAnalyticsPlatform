<?php

namespace App\Services;

use App\Events\DeviceTelemetryReceived;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotIngestionLog;
use App\Services\Anomaly\AlertRouting;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use App\Services\Iot\TelemetryPayloadRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class IotHeartbeatProcessingService
{
    /** A device stuck sending bad heartbeats emails its hardware team at most this often. */
    private const MALFORMED_NOTICE_MINUTES = 60;

    public function __construct(private readonly AnomalyAlertDispatchService $alerts)
    {
    }

    /**
     * @param  string|null  $receivedAt  when the gateway received the request (envelope requestTime)
     */
    public function store(IotDevice $device, array $payload, ?string $receivedAt = null): void
    {
        // UC-IOT-01, Alternative Flow B: nothing from a malformed heartbeat
        // is stored. Queue-delivered, so "rejected" means logged and
        // acknowledged rather than an HTTP 422.
        $errors = TelemetryPayloadRules::errors($payload);

        if ($errors !== []) {
            $this->reject($device, $errors);

            return;
        }

        // When the heartbeat was sent, not when the queue worker got to it:
        // the device's own recorded_at, else the gateway's receive time.
        $heartbeatAt = $this->timestamp($payload['recorded_at'] ?? null)
            ?? $this->timestamp($receivedAt)
            ?? now();

        $reading = [
            'battery_level' => $payload['battery_level'] ?? null,
            'signal_strength' => $payload['signal_strength'] ?? null,
            'uptime_seconds' => $payload['uptime'] ?? null,
            'firmware_version' => $payload['firmware_version'] ?? null,
            'cpu_usage' => $payload['cpu_usage'] ?? null,
            'storage_usage' => $payload['storage_usage'] ?? null,
            'reboot_count' => $payload['reboot_count'] ?? 0,
            'sensor_read_success_rate' => $payload['sensor_read_success_rate'] ?? null,
            'error_codes' => $payload['error_codes'] ?? [],
        ];

        // SQS standard queues don't preserve order: an older heartbeat
        // processed late must not overwrite the device's current state.
        $lastHeartbeatAt = IotDeviceTelemetry::where('device_id', $device->id)->value('last_heartbeat_at');
        $isLatest = $lastHeartbeatAt === null || $heartbeatAt->greaterThanOrEqualTo(Carbon::parse($lastHeartbeatAt));

        if ($isLatest) {
            IotDeviceTelemetry::updateOrCreate(
                ['device_id' => $device->id],
                $reading + ['last_heartbeat_at' => $heartbeatAt],
            );

            if (! empty($payload['firmware_version'])) {
                $device->update(['firmware_version' => $payload['firmware_version']]);
            }
        }

        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'heartbeat',
            'outcome' => 'accepted',
            'created_at' => now(),
        ]);

        event(new DeviceTelemetryReceived($device, $reading, $heartbeatAt, $isLatest));
    }

    /** @param  array<string, array<int, string>>  $errors */
    private function reject(IotDevice $device, array $errors): void
    {
        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'heartbeat',
            'outcome' => 'rejected_validation',
            'validation_errors' => $errors,
            'created_at' => now(),
        ]);

        if (! Cache::add("iot:malformed_heartbeat_notice:{$device->id}", true, now()->addMinutes(self::MALFORMED_NOTICE_MINUTES))) {
            return;
        }

        $problems = collect($errors)->map(fn (array $messages, string $field) => "- {$field}: ".implode(' ', $messages))->implode("\n");

        $this->alerts->notifyStaff(
            $device,
            AlertRouting::MALFORMED_HEARTBEAT,
            'malformed_heartbeat',
            "Malformed heartbeat from device {$device->device_code}",
            "Device {$device->device_code} sent a heartbeat that failed validation, so it was not stored.\n\n{$problems}\n\n"
                .'Further malformed heartbeats from this device are logged but not emailed for the next '.self::MALFORMED_NOTICE_MINUTES.' minutes.',
        );
    }

    /** Parses an ISO-8601 time; null when missing or invalid. Future times are clamped to now. */
    private function timestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $time = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $time->isFuture() ? now() : $time;
    }
}

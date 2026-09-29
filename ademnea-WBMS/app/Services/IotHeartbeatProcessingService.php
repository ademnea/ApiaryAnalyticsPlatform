<?php

namespace App\Services;

use App\Events\DeviceTelemetryReceived;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotIngestionLog;
use Illuminate\Support\Carbon;

class IotHeartbeatProcessingService
{
    /**
     * @param  string|null  $receivedAt  when the gateway received the request (envelope requestTime)
     */
    public function store(IotDevice $device, array $payload, ?string $receivedAt = null): void
    {
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

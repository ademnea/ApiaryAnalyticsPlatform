<?php

namespace App\Services;

use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotIngestionLog;

class IotHeartbeatProcessingService
{
    public function store(IotDevice $device, array $payload): void
    {
        IotDeviceTelemetry::updateOrCreate(
            ['device_id' => $device->id],
            [
                'battery_level' => $payload['battery_level'] ?? null,
                'signal_strength' => $payload['signal_strength'] ?? null,
                'uptime_seconds' => $payload['uptime'] ?? null,
                'firmware_version' => $payload['firmware_version'] ?? null,
                'cpu_usage' => $payload['cpu_usage'] ?? null,
                'storage_usage' => $payload['storage_usage'] ?? null,
                'reboot_count' => $payload['reboot_count'] ?? 0,
                'sensor_read_success_rate' => $payload['sensor_read_success_rate'] ?? null,
                'error_codes' => $payload['error_codes'] ?? [],
                'last_heartbeat_at' => now(),
            ]
        );

        if (! empty($payload['firmware_version'])) {
            $device->update(['firmware_version' => $payload['firmware_version']]);
        }

        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'heartbeat',
            'outcome' => 'accepted',
            'created_at' => now(),
        ]);

       event(new \App\Events\DeviceTelemetryReceived($device));
    }
}
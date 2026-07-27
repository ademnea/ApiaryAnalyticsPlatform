<?php

namespace App\Services;

use App\Models\IotAuthLog;
use App\Models\IotDevice;
use Illuminate\Support\Facades\Hash;

class IotDeviceAuthenticationService
{
    public function resolveDevice(string $apiKey): ?IotDevice
    {
        if ($apiKey === '') {
            return null;
        }
        

        return IotDevice::query()
            ->where('active_flag', true)
            ->get()
            ->first(fn (IotDevice $device) => Hash::check($apiKey, $device->api_key_hash));
    }

    public function logAttempt(?IotDevice $device, string $ipAddress, string $endpoint, string $eventType): void
    {
        IotAuthLog::create([
            'device_id' => $device?->id,
            'event_type' => $eventType,
            'ip_address' => $ipAddress,
            'endpoint' => $endpoint,
            'created_at' => now(),
        ]);
    }
}
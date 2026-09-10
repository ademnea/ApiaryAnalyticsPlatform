<?php

namespace App\Services;

use App\Contracts\MediaUploadStorageContract;
use App\Models\IotDevice;
use App\Models\IotIngestionLog;
use App\Models\HivePhoto;
use App\Models\HiveVideo;
use App\Models\HiveAudio;

class IotMediaConfirmationService
{
    public function __construct(
        private readonly MediaUploadStorageContract $storage,
        private readonly IotDeviceIdentificationService $identification,
    ) {
    }

    public function confirm(IotDevice $device, array $payload): void
    {
        $objectKey = $payload['s3_object_key'] ?? null;
        $mediaType = $payload['media_type'] ?? null;

        if (! $objectKey || ! $mediaType) {
            $this->logRejected($device, 'Missing s3_object_key or media_type.');
            return;
        }

        // Defensive check per SRS UC-IOT-07 alt flow: a device could
        // confirm an upload that never actually completed.
        if (! $this->storage->objectExists($objectKey)) {
            $this->logRejected($device, "Confirmed object not found in storage: {$objectKey}");
            return;
        }

        try {
        ['hive' => $hive] = $this->identification->resolveHiveAndApiary($device);
    } catch (\App\Exceptions\IotDeviceNotAssignedException $e) {
        $this->logRejected($device, $e->getMessage());
        return;
    }

        $attrs = [
            'hive_id' => $hive->id,
            'device_id' => $device->id,
            'file_path' => $objectKey,
            's3_object_key' => $objectKey,
            'file_size_bytes' => $payload['file_size_bytes'] ?? null,
            'recorded_at' => \Illuminate\Support\Carbon::parse($payload['captured_at'] ?? now())->utc(),
            'created_at' => now(),
        ];

        match ($mediaType) {
            'photo' => HivePhoto::create($attrs),
            'video' => HiveVideo::create($attrs + ['duration_seconds' => $payload['duration_seconds'] ?? null]),
            'audio' => HiveAudio::create($attrs + ['duration_seconds' => $payload['duration_seconds'] ?? null]),
            default => $this->logRejected($device, "Unknown media_type: {$mediaType}"),
        };

        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'media',
            'outcome' => 'accepted',
            'payload_size_bytes' => $payload['file_size_bytes'] ?? null,
            'created_at' => now(),
        ]);
    }

    private function logRejected(IotDevice $device, string $reason): void
    {
        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'media',
            'outcome' => 'rejected_validation',
            'validation_errors' => ['reason' => $reason],
            'created_at' => now(),
        ]);
    }
}
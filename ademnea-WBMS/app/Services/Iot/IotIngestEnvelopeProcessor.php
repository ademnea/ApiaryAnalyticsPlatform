<?php

namespace App\Services\Iot;

use App\Services\IotDeviceAuthenticationService;
use App\Services\IotHeartbeatProcessingService;
use App\Services\IotMediaConfirmationService;
use App\Services\IotSensorIngestionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

class IotIngestEnvelopeProcessor
{
    public function __construct(
        private readonly IotDeviceAuthenticationService $auth,
        private readonly IotSensorIngestionService $sensorService,
        private readonly IotHeartbeatProcessingService $heartbeatService,
        private readonly IotMediaConfirmationService $mediaService,
    ) {}

    /**
     * Throws on genuinely transient failure (caught by the worker command,
     * which hands it to the transport's fail() for requeue/dead-letter).
     * Returns normally on success OR on a permanent condition (bad auth,
     * duplicate delivery) — both of those are "acknowledge and move on."
     */
    public function process(string $messageType, array $payload, ?string $apiKey): void
    {
        $device = $this->auth->resolveDevice($apiKey ?? '');

        if (! $device) {
            $this->auth->logAttempt(null, 'queue-worker', $messageType, 'auth_failure');
            Log::warning('IoT ingest rejected — invalid or revoked API key.', ['message_type' => $messageType]);
            return; // permanent — never retry a bad key
        }

        $this->auth->logAttempt($device, 'queue-worker', $messageType, 'auth_success');

        try {
            match ($messageType) {
                'sensor_reading' => $this->sensorService->store($device, $payload),
                'heartbeat' => $this->heartbeatService->store($device, $payload),
                'media_confirmation' => $this->mediaService->confirm($device, $payload),
                default => throw new \InvalidArgumentException("Unknown message_type: {$messageType}"),
            };
        } catch (UniqueConstraintViolationException) {
            // At-least-once delivery duplicate — success, not failure.
            Log::info('Duplicate IoT message discarded (already processed).', [
                'device_id' => $device->id,
                'message_type'
                 => $messageType,
            ]);
        }
    }
}
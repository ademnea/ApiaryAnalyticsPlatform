<?php

namespace App\Services;

use App\Models\IotDevice;
use App\Models\IotIngestionLog;
use App\Models\HiveTemperature;
use App\Models\HiveHumidity;
use App\Models\HiveCarbondioxide;
use App\Models\HiveWeight;
use Illuminate\Support\Facades\DB;

class IotSensorIngestionService
{
    public function __construct(private readonly IotDeviceIdentificationService $identification)
    {
    }

    public function store(IotDevice $device, array $payload): void
    {
        $sensorType = $payload['sensor_type'] ?? null;
        $recordedAt = $payload['recorded_at'] ?? null;
        $reading = $payload['reading'] ?? [];

        if (! $sensorType || ! $recordedAt) {
            $this->logRejected($device, $payload, 'Missing sensor_type or recorded_at.');
            return;
        }

        ['hive' => $hive] = $this->identification->resolveHiveAndApiary($device);

         try {
           ['hive' => $hive] = $this->identification->resolveHiveAndApiary($device);
             } catch (\App\Exceptions\IotDeviceNotAssignedException $e) {
           $this->logRejected($device, $payload, $e->getMessage());
        return;
    }

        $recordedAtUtc = \Illuminate\Support\Carbon::parse($recordedAt)->utc();

        // Discrete typed columns per Schema Rule 7 — the *-delimited
        // string convention is retired. Each sensor type maps to its own
        // table and its own shape, matched explicitly rather than by a
        // single generic branch, since temperature/humidity are 3-zone
        // and co2/weight are single-value.
        match ($sensorType) {
            'temperature' => HiveTemperature::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'honey_section' => $reading['honey_section'] ?? null,
                'brood_section' => $reading['brood_section'] ?? null,
                'exterior' => $reading['exterior'] ?? null,
                'suspect' => false,
                'recorded_at' => $recordedAtUtc,
                'created_at' => now(),
            ]),
            'humidity' => HiveHumidity::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'honey_section' => $reading['honey_section'] ?? null,
                'brood_section' => $reading['brood_section'] ?? null,
                'exterior' => $reading['exterior'] ?? null,
                'suspect' => false,
                'recorded_at' => $recordedAtUtc,
                'created_at' => now(),
            ]),
            'co2' => HiveCarbondioxide::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'co2_level' => $reading['co2_level'] ?? null,
                'suspect' => false,
                'recorded_at' => $recordedAtUtc,
                'created_at' => now(),
            ]),
            'weight' => HiveWeight::create([
                'hive_id' => $hive->id,
                'device_id' => $device->id,
                'weight_kg' => $reading['weight_kg'] ?? null,
                'suspect' => false,
                'recorded_at' => $recordedAtUtc,
                'created_at' => now(),
            ]),
            default => $this->logRejected($device, $payload, "Unknown sensor_type: {$sensorType}"),
        };

        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'sensor_data',
            'outcome' => 'accepted',
            'created_at' => now(),
        ]);

        // Event dispatch to IoT Condition Monitoring stays exactly as
        // already designed in §4.5.8 — decoupled, not called directly.
        event(new \App\Events\SensorRecordReceived($device, $hive, $sensorType, $recordedAtUtc));
    }

    private function logRejected(IotDevice $device, array $payload, string $reason): void
    {
        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'sensor_data',
            'outcome' => 'rejected_validation',
            'validation_errors' => ['reason' => $reason],
            'created_at' => now(),
        ]);
    }
}
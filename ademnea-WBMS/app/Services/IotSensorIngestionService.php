<?php

namespace App\Services;

use App\Exceptions\IotDeviceNotAssignedException;
use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotIngestionLog;
use App\Models\HiveTemperature;
use App\Models\HiveHumidity;
use App\Models\HiveCarbondioxide;
use App\Models\HiveWeight;
use App\Services\Iot\TelemetryPayloadRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IotSensorIngestionService
{
    private const SENSOR_TYPES = ['temperature', 'humidity', 'co2', 'weight'];

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

        // Rejected before anything is stored, so an unknown type is never
        // also logged as accepted or announced to condition monitoring.
        if (! in_array($sensorType, self::SENSOR_TYPES, true)) {
            $this->logRejected($device, $payload, "Unknown sensor_type: {$sensorType}");
            return;
        }

        // An unassigned device is a permanent condition: log and acknowledge.
        // Letting the exception escape would make the queue worker retry the
        // message as if it were a transient failure.
        try {
            ['hive' => $hive] = $this->identification->resolveHiveAndApiary($device);
        } catch (IotDeviceNotAssignedException $e) {
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
        };

        IotIngestionLog::create([
            'device_id' => $device->id,
            'payload_type' => 'sensor_data',
            'outcome' => 'accepted',
            'created_at' => now(),
        ]);

        // Receive time, not recorded_at: a device uploading a backlog of old
        // readings is still in contact. CheckDeviceHealth counts this as
        // contact, so a device that sends data but no heartbeats isn't
        // reported offline.
        IotDeviceTelemetry::updateOrCreate(
            ['device_id' => $device->id],
            ['last_data_received_at' => now()] + $this->deviceMeta($device, $payload['device_meta'] ?? null, $recordedAtUtc),
        );

        // Event dispatch to IoT Condition Monitoring stays exactly as
        // already designed in §4.5.8 — decoupled, not called directly.
        event(new \App\Events\SensorRecordReceived($device, $hive, $sensorType, $recordedAtUtc));
    }

    /**
     * UC-IOT-02: battery, signal and firmware piggybacked on a reading, so
     * the dashboard sees them more often than every heartbeat. Optional —
     * older firmware doesn't send it — and ignored when the reading is older
     * than the last heartbeat, which already holds newer values.
     *
     * @return array<string, mixed> telemetry columns to update
     */
    private function deviceMeta(IotDevice $device, mixed $meta, Carbon $recordedAt): array
    {
        $meta = TelemetryPayloadRules::validDeviceMeta($meta);

        if ($meta === []) {
            return [];
        }

        $lastHeartbeatAt = IotDeviceTelemetry::where('device_id', $device->id)->value('last_heartbeat_at');

        if ($lastHeartbeatAt !== null && $recordedAt->lt(Carbon::parse($lastHeartbeatAt))) {
            return [];
        }

        if (isset($meta['firmware_version'])) {
            $device->update(['firmware_version' => $meta['firmware_version']]);
        }

        return $meta;
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
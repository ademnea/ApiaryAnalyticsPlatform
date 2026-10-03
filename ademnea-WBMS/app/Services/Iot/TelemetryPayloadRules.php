<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Validator;

/**
 * The valid shape of device telemetry, shared by heartbeats (UC-IOT-01) and
 * the device_meta object piggybacked on sensor readings (UC-IOT-02).
 *
 * Every field is optional: older firmware reports fewer of them, and even an
 * empty heartbeat proves the device is alive. A field that is present must
 * be well formed.
 */
final class TelemetryPayloadRules
{
    public const RULES = [
        'battery_level' => ['nullable', 'numeric', 'between:0,100'],
        'signal_strength' => ['nullable', 'numeric', 'between:-150,0'],
        'uptime' => ['nullable', 'integer', 'min:0'],
        'firmware_version' => ['nullable', 'string', 'max:30'],
        'cpu_usage' => ['nullable', 'numeric', 'between:0,100'],
        'storage_usage' => ['nullable', 'numeric', 'between:0,100'],
        'reboot_count' => ['nullable', 'integer', 'min:0'],
        'sensor_read_success_rate' => ['nullable', 'numeric', 'between:0,1'],
        'error_codes' => ['nullable', 'array'],
    ];

    /** The fields device_meta may carry (REQ-F-IOT-02). */
    public const DEVICE_META_FIELDS = ['battery_level', 'signal_strength', 'firmware_version'];

    /** @return array<string, array<int, string>> field => messages; empty when valid */
    public static function errors(array $payload): array
    {
        return Validator::make($payload, self::RULES)->errors()->toArray();
    }

    /**
     * The well-formed device_meta fields; malformed ones are dropped, since
     * device_meta is extra and must never fail the reading it rides on.
     *
     * @return array<string, mixed>
     */
    public static function validDeviceMeta(mixed $meta): array
    {
        if (! is_array($meta)) {
            return [];
        }

        $meta = array_intersect_key($meta, array_flip(self::DEVICE_META_FIELDS));
        $invalid = self::errors($meta);

        return array_filter(
            array_diff_key($meta, $invalid),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}

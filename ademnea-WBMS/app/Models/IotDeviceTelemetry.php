<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDeviceTelemetry extends Model
{
    protected $table = 'iot_device_telemetry';

    protected $fillable = [
        'device_id', 'battery_level', 'signal_strength', 'uptime_seconds',
        'firmware_version', 'cpu_usage', 'storage_usage', 'reboot_count',
        'sensor_read_success_rate', 'error_codes',
        'last_heartbeat_at', 'last_data_received_at',
        'data_gap_minutes', 'submission_interval_actual',
    ];

    protected $casts = [
        'error_codes' => 'array',
        'last_heartbeat_at' => 'datetime',
        'last_data_received_at' => 'datetime',
        'battery_level' => 'float',
        'signal_strength' => 'float',
        'cpu_usage' => 'float',
        'storage_usage' => 'float',
        'sensor_read_success_rate' => 'float',
        'submission_interval_actual' => 'float',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
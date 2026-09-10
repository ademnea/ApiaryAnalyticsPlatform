<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDeviceTelemetryHistory extends Model
{
    protected $table = 'iot_device_telemetry_history';

    // Append-only: no updated_at column.
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'battery_level', 'signal_strength', 'uptime_seconds',
        'cpu_usage', 'storage_usage', 'reboot_count', 'sensor_read_success_rate',
        'error_codes', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'error_codes' => 'array',
        'battery_level' => 'float',
        'signal_strength' => 'float',
        'cpu_usage' => 'float',
        'storage_usage' => 'float',
        'sensor_read_success_rate' => 'float',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class IotDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_devices';

    // api_key_hash is deliberately excluded from $fillable.
    // It is only ever written by IotDeviceRegistryService::register(),
    // never through mass assignment on a generic update.
    protected $fillable = [
        'device_code',
        'device_type',
        'hardware_team_id',
        'hive_id',
        'firmware_version',
        'firmware_notes',
        'hardware_revision',
        'expected_interval_minutes',
        'status',
        
    ];

    protected $casts = [
        'active_flag' => 'boolean',
        'expected_interval_minutes' => 'integer',
    ];

    public function hardwareTeam(): BelongsTo
    {
        return $this->belongsTo(IotHardwareTeam::class, 'hardware_team_id');
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class, 'hive_id');
    }

    // public function telemetry(): HasOne
    // {
    //     return $this->hasOne(IotDeviceTelemetry::class, 'device_id');
    // }

    public function authLogs(): HasMany
    {
        return $this->hasMany(IotAuthLog::class, 'device_id');
    }

    // public function ingestionLogs(): HasMany
    // {
    //     return $this->hasMany(IotIngestionLog::class, 'device_id');
    // }
}
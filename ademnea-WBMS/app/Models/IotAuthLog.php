<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotAuthLog extends Model
{
    protected $table = 'iot_auth_logs';

    // Append-only log: no updated_at column exists.
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'event_type',
        'ip_address',
        'endpoint',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
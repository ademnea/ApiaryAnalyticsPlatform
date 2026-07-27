<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotIngestionLog extends Model
{
    protected $table = 'iot_ingestion_logs';

    public $timestamps = false; // append-only, no updated_at

    protected $fillable = [
        'device_id', 'payload_type', 'outcome', 'validation_errors',
        'payload_size_bytes', 'created_at',
    ];

    protected $casts = [
        'validation_errors' => 'array',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
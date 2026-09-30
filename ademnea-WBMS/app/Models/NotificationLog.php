<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One delivery attempt of one notification to one recipient on one channel.
 * status: pending → sent | failed, or skipped when the channel isn't
 * configured or the recipient has no address for it.
 */
class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'recipient_type',
        'recipient',
        'sensor_anomaly_id',
        'type',
        'channel',
        'subject',
        'content',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'farmer_id' => 'integer',
        'sensor_anomaly_id' => 'integer',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function sensorAnomaly(): BelongsTo
    {
        return $this->belongsTo(SensorAnomaly::class);
    }
}

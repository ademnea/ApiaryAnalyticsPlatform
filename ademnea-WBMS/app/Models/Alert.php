<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'farmer_id',
        'hive_id',
        'source_anomaly_id',
        'type',
        'message',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'farmer_id'          => 'integer',
        'hive_id'            => 'integer',
        'source_anomaly_id'  => 'integer',
        'is_read'            => 'boolean',
        'created_at'         => 'datetime',
        'read_at'            => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function sourceAnomaly(): BelongsTo
    {
        return $this->belongsTo(SensorAnomaly::class, 'source_anomaly_id');
    }

    public function isCooldownExempt(): bool
    {
        return $this->type === 'malfunction';
    }

    /**
     * Per-type cooldown window, in minutes, before a repeat alert of the
     * same type/hive is allowed to fire again. Null means no cooldown —
     * every occurrence alerts (malfunction/critical_event fire always;
     * device_offline has no cooldown because it's gated by "unresolved
     * anomaly exists" instead, until recovery).
     */
    public function cooldownMinutesFor(string $type): ?int
    {
        return match ($type) {
            'malfunction', 'critical_event' => null,
            'critical_battery' => 15,
            'device_offline' => null,
            default => 60,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveWeight extends Model
{
    protected $table = 'hive_weights';

    public $timestamps = false;

    protected $fillable = [
        'hive_id', 'device_id', 'weight_kg', 'suspect', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'suspect' => 'boolean',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'weight_kg' => 'float',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
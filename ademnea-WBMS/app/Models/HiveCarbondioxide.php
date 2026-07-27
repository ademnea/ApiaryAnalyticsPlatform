<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveCarbondioxide extends Model
{
    protected $table = 'hive_carbondioxide';

    public $timestamps = false;

    protected $fillable = [
        'hive_id', 'device_id', 'co2_level', 'suspect', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'suspect' => 'boolean',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'co2_level' => 'float',
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveTemperature extends Model
{
    protected $table = 'hive_temperatures';

    // Append-only: no updated_at column. created_at is set explicitly by
    // the ingestion service rather than relying on Eloquent's automatic
    // timestamp behavior, matching Schema Rule 4's documented exception.
    public $timestamps = false;

    protected $fillable = [
        'hive_id', 'device_id', 'honey_section', 'brood_section',
        'exterior', 'suspect', 'recorded_at', 'created_at',
    ];

    protected $casts = [
        'suspect' => 'boolean',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'honey_section' => 'float',
        'brood_section' => 'float',
        'exterior' => 'float',
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveHumidity extends Model
{
    protected $table = 'hive_humidities';

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
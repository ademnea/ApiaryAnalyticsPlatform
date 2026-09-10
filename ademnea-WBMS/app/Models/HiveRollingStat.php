<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveRollingStat extends Model
{
    protected $table = 'hive_rolling_stats';

    protected $fillable = [
        'hive_id', 'sensor_type', 'channel', 'mean', 'variance', 'sample_count', 'window_start',
    ];

    protected $casts = [
        'mean' => 'float',
        'variance' => 'float',
        'sample_count' => 'integer',
        'window_start' => 'datetime',
    ];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }
}

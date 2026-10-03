<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot of a hive's latest rolling 24-hour window per sensor channel,
 * written by RollingStatsService::recordSnapshot(). `variance` is the
 * population variance of the window (it held Welford's M2 accumulator
 * before the window became a true sliding one).
 */
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

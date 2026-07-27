<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AlertThreshold extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    /**
     * REQ-F-FAPI-24: thresholds are read on every hourly job run — cache
     * briefly so the scheduled job doesn't hammer the DB for static config.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("alert_threshold:{$key}", 300, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }
}

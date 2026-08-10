<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AlertThreshold extends Model
{
    protected $fillable = ['key', 'value', 'description', 'hive_id'];

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("alert_threshold:global:{$key}", 300, function () use ($key, $default) {
            return static::where('key', $key)->whereNull('hive_id')->value('value') ?? $default;
        });
    }

    public static function getForHive(int $hiveId, string $key, mixed $default = null): mixed
    {
        return Cache::remember("alert_threshold:hive:{$hiveId}:{$key}", 300, function () use ($hiveId, $key, $default) {
            $hiveSpecific = static::where('hive_id', $hiveId)->where('key', $key)->value('value');

            if ($hiveSpecific !== null) {
                return $hiveSpecific;
            }

            return static::where('key', $key)->whereNull('hive_id')->value('value') ?? $default;
        });
    }
}

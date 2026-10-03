<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AlertThreshold extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'description', 'hive_id'];

    /**
     * get() and getForHive() cache for five minutes. A saved or deleted row
     * drops the cached values it feeds, so a changed limit applies to the
     * next reading instead of up to five minutes later.
     */
    protected static function booted(): void
    {
        $forget = function (AlertThreshold $threshold) {
            // During these events getOriginal() still holds the values before the change.
            $hiveIds = array_filter([$threshold->hive_id, $threshold->getOriginal('hive_id')]);
            $touchesFleet = $threshold->hive_id === null || $threshold->getOriginal('hive_id') === null;

            // A fleet value is also what every hive without an override resolves to.
            if ($touchesFleet) {
                $hiveIds = Hive::pluck('id')->all();
            }

            foreach (array_unique(array_filter([$threshold->key, $threshold->getOriginal('key')])) as $key) {
                if ($touchesFleet) {
                    Cache::forget("alert_threshold:global:{$key}");
                }

                foreach ($hiveIds as $hiveId) {
                    Cache::forget("alert_threshold:hive:{$hiveId}:{$key}");
                }
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }

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

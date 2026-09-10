<?php

namespace App\Services\Anomaly;

use App\Models\HiveRollingStat;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Incremental Welford update against hive_rolling_stats, fronted by a short
 * cache TTL, mirroring AlertThreshold::get()'s idiom. Uses a tumbling 24h
 * window (reset on schedule), not a true sliding window — see SDD §4.4.9(b).
 *
 * The `variance` column stores Welford's M2 accumulator, not the final
 * variance — callers divide by sample_count to get the actual variance.
 */
class RollingStatsService
{
    private const TUMBLING_WINDOW_HOURS = 24;
    private const CACHE_TTL_SECONDS = 60;

    /**
     * Read-only, cached lookup of the stats as they stood before the
     * current reading. Callers evaluating an incoming reading against the
     * rolling baseline should use this, not updateAndGet(), so a reading
     * is never evaluated against a baseline it has already skewed.
     */
    public function currentStats(int $hiveId, string $sensorType, ?string $channel): ?HiveRollingStat
    {
        return Cache::remember(
            $this->cacheKey($hiveId, $sensorType, $channel),
            self::CACHE_TTL_SECONDS,
            fn () => HiveRollingStat::where('hive_id', $hiveId)
                ->where('sensor_type', $sensorType)
                ->where('channel', $channel)
                ->first()
        );
    }

    /**
     * Updates the rolling stats for every channel present on $reading.
     * Called once per ingested reading, after rule evaluation, regardless
     * of anomaly outcome — Welford's algorithm requires every reading to
     * update the running stats, not just non-anomalous ones.
     */
    public function updateAllChannels(Model $reading, string $sensorType): void
    {
        foreach (SensorChannels::channelsFor($sensorType) as $channel) {
            $value = $reading->{SensorChannels::columnFor($sensorType, $channel)};

            if ($value !== null) {
                $this->updateAndGet((int) $reading->hive_id, $sensorType, $channel, (float) $value);
            }
        }
    }

    public function updateAndGet(int $hiveId, string $sensorType, ?string $channel, float $value): HiveRollingStat
    {
        $stats = HiveRollingStat::firstOrCreate(
            ['hive_id' => $hiveId, 'sensor_type' => $sensorType, 'channel' => $channel],
            ['mean' => 0, 'variance' => 0, 'sample_count' => 0, 'window_start' => null]
        );

        $windowExpired = ! $stats->window_start
            || $stats->window_start->diffInHours(now()) >= self::TUMBLING_WINDOW_HOURS;

        if ($windowExpired) {
            $stats->mean = $value;
            $stats->variance = 0;
            $stats->sample_count = 1;
            $stats->window_start = now();
        } else {
            $stats->sample_count++;
            $delta = $value - $stats->mean;
            $stats->mean += $delta / $stats->sample_count;
            $delta2 = $value - $stats->mean;
            $stats->variance += $delta * $delta2; // M2 accumulator
        }

        $stats->save();

        Cache::forget($this->cacheKey($hiveId, $sensorType, $channel));

        return $stats;
    }

    private function cacheKey(int $hiveId, string $sensorType, ?string $channel): string
    {
        return "rolling_stats:{$hiveId}:{$sensorType}:" . ($channel ?? 'null');
    }
}

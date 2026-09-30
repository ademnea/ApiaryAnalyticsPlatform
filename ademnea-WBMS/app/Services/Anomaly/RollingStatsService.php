<?php

namespace App\Services\Anomaly;

use App\Models\HiveRollingStat;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The rolling 24-hour baseline for the z-score check (REQ-F-IOT-07): mean
 * and standard deviation of a hive's own readings, per sensor channel, over
 * the 24 hours before the reading being judged.
 *
 * A true sliding window, computed in SQL on the (hive_id, recorded_at)
 * index — no daily reset, and a backlog reading is judged against the day
 * before it was taken, not the day before it arrived. Suspect readings are
 * left out so a broken sensor can't widen the baseline and hide itself.
 *
 * hive_rolling_stats keeps the latest window per (hive, sensor, channel) as
 * a snapshot for display; the rules never read it.
 */
class RollingStatsService
{
    public const WINDOW_HOURS = 24;

    /**
     * The baseline as it stood just before $reading, which is not included.
     *
     * @return array{mean: float, stddev: float, count: int}|null null when the window holds no readings
     */
    public function baselineBefore(Model $reading, string $sensorType, ?string $channel): ?array
    {
        return $this->window($reading, $sensorType, $channel, includeReading: false);
    }

    /** Updates the hive_rolling_stats snapshot for every channel on $reading, the reading included. */
    public function recordSnapshot(Model $reading, string $sensorType): void
    {
        foreach (SensorChannels::channelsFor($sensorType) as $channel) {
            $window = $this->window($reading, $sensorType, $channel, includeReading: true);

            if ($window === null) {
                continue;
            }

            HiveRollingStat::updateOrCreate(
                ['hive_id' => $reading->hive_id, 'sensor_type' => $sensorType, 'channel' => $channel],
                [
                    'mean' => $window['mean'],
                    'variance' => $window['stddev'] ** 2,
                    'sample_count' => $window['count'],
                    'window_start' => Carbon::parse($reading->recorded_at)->subHours(self::WINDOW_HOURS),
                ],
            );
        }
    }

    /** @return array{mean: float, stddev: float, count: int}|null */
    private function window(Model $reading, string $sensorType, ?string $channel, bool $includeReading): ?array
    {
        $column = SensorChannels::columnFor($sensorType, $channel);
        $end = Carbon::parse($reading->recorded_at);

        $row = $reading->newQuery()
            ->where('hive_id', $reading->hive_id)
            ->where('recorded_at', '>=', $end->copy()->subHours(self::WINDOW_HOURS))
            ->where('recorded_at', $includeReading ? '<=' : '<', $end)
            ->where('suspect', false)
            ->whereNotNull($column)
            ->toBase()
            ->selectRaw("COUNT({$column}) as sample_count, AVG({$column}) as mean, STDDEV_POP({$column}) as stddev")
            ->first();

        if (! $row || (int) $row->sample_count === 0) {
            return null;
        }

        return ['mean' => (float) $row->mean, 'stddev' => (float) $row->stddev, 'count' => (int) $row->sample_count];
    }
}

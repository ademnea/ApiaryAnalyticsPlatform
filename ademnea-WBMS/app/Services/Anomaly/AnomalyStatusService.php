<?php

namespace App\Services\Anomaly;

use App\Contracts\AnomalyStatusContract;
use App\Models\SensorAnomaly;
use Illuminate\Support\Collection;

class AnomalyStatusService implements AnomalyStatusContract
{
    public function hasUnresolvedAnomalies(int $hiveId): bool
    {
        return SensorAnomaly::where('hive_id', $hiveId)
            ->where('resolved', false)
            ->exists();
    }

    public function latestAnomalies(int $hiveId, int $limit = 5): Collection
    {
        return SensorAnomaly::where('hive_id', $hiveId)
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get();
    }
}

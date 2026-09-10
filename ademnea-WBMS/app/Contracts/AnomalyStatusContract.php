<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Owned by the IoT Condition Monitoring module. Other modules that need to
 * know a hive's anomaly status should depend on this interface rather than
 * querying sensor_anomalies directly. The real implementation is
 * App\Services\Anomaly\AnomalyStatusService.
 *
 * Do not change these two method signatures without coordinating — no
 * confirmed consumer yet for v1 (SDD Open Item #6), but the shape is fixed
 * intentionally so a future consumer doesn't need this module's internals.
 */
interface AnomalyStatusContract
{
    /**
     * Whether the given hive has at least one unresolved SensorAnomaly.
     * A hive can have a live unresolved anomaly with no recent Alert row
     * (alerts are suppressible via cooldown; anomalies are not).
     */
    public function hasUnresolvedAnomalies(int $hiveId): bool;

    /**
     * The most recent SensorAnomaly rows for a hive, newest first.
     */
    public function latestAnomalies(int $hiveId, int $limit = 5): Collection;
}

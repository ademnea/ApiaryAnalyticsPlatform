<?php

namespace App\Listeners\Anomaly;

use App\Events\SensorRecordReceived;
use App\Models\SensorAnomaly;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use App\Services\Anomaly\RollingStatsService;
use App\Services\Anomaly\RulesEngine\StuckSensorRuleEvaluator;
use App\Services\Anomaly\RulesEngine\Support\SensorChannels;
use App\Services\Anomaly\RulesEngine\ThresholdRuleEvaluator;
use App\Services\Anomaly\RulesEngine\ZScoreRuleEvaluator;

/**
 * Runs synchronously in the same call stack as ingestion (SDD §4.4.9(a)) —
 * not queued — so flagging happens deterministically before the IoT queue
 * worker acknowledges the message.
 */
class EvaluateSensorReadingRules
{
    /**
     * Anomaly types that mean the reading itself can't be trusted, so it is
     * marked suspect and left out of averages. statistical_deviation is not
     * here: an unusual value can be a real colony event, not bad data.
     */
    private const SUSPECT_TYPES = ['static_threshold_breach', 'frozen_sensor'];

    public function __construct(
        private readonly ThresholdRuleEvaluator $thresholdRule,
        private readonly StuckSensorRuleEvaluator $stuckSensorRule,
        private readonly ZScoreRuleEvaluator $zScoreRule,
        private readonly AnomalyAlertDispatchService $dispatchService,
        private readonly RollingStatsService $rollingStats,
    ) {
    }

    public function handle(SensorRecordReceived $event): void
    {
        $class = SensorChannels::modelClassFor($event->sensorType);

        $reading = $class::where('device_id', $event->device->id)
            ->where('recorded_at', $event->recordedAt)
            ->latest('id')
            ->first();

        if (! $reading) {
            return;
        }

        // Stop at first violation found, per SRS. Every rule that ran and
        // came back clean closes its open incident for this device/hive/
        // sensor; rules skipped by the short-circuit are left untouched.
        $rules = [
            'static_threshold_breach' => fn () => $this->thresholdRule->evaluate($reading, $event->sensorType),
            'frozen_sensor' => fn () => $this->stuckSensorRule->evaluate($reading, $event->sensorType),
            'statistical_deviation' => fn () => $this->zScoreRule->evaluate($reading, $event->sensorType),
        ];

        $anomaly = null;
        $cleanTypes = [];

        foreach ($rules as $type => $rule) {
            $anomaly = $rule();

            if ($anomaly) {
                break;
            }

            $cleanTypes[] = $type;
        }

        if ($anomaly && in_array($anomaly->anomaly_type, self::SUSPECT_TYPES, true)) {
            $reading->update(['suspect' => true]);
        }

        SensorAnomaly::autoResolve(
            (int) $reading->device_id,
            $event->sensorType,
            $cleanTypes,
            $reading->hive_id !== null ? (int) $reading->hive_id : null,
        );

        // Alert only when an incident is first opened, not on every repeat.
        if ($anomaly?->wasRecentlyCreated) {
            $this->dispatchService->dispatch($anomaly);
        }

        // Snapshot for display only; the z-score check computes its own
        // baseline from the readings before this one.
        $this->rollingStats->recordSnapshot($reading, $event->sensorType);
    }
}

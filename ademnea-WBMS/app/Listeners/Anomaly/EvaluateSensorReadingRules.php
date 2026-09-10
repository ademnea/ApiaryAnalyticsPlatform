<?php

namespace App\Listeners\Anomaly;

use App\Events\SensorRecordReceived;
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

        // Stop at first violation found, per SRS.
        $anomaly = $this->thresholdRule->evaluate($reading, $event->sensorType)
            ?? $this->stuckSensorRule->evaluate($reading, $event->sensorType)
            ?? $this->zScoreRule->evaluate($reading, $event->sensorType);

        if ($anomaly) {
            $this->dispatchService->dispatch($anomaly);
        }

        // Always update, regardless of anomaly outcome — Welford's algorithm
        // needs every reading, and ZScoreRuleEvaluator above already read
        // the pre-update baseline via RollingStatsService::currentStats().
        $this->rollingStats->updateAllChannels($reading, $event->sensorType);
    }
}

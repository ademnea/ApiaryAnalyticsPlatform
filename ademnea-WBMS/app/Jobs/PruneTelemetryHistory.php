<?php

namespace App\Jobs;

use App\Models\AlertThreshold;
use App\Models\IotDeviceTelemetryHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SDD Open Item #5 default: prune iot_device_telemetry_history rows older
 * than the configured retention window, so per-heartbeat history doesn't
 * grow unbounded with fleet size. Retention length is an admin-overridable
 * AlertThreshold row, not a hardcoded constant.
 */
class PruneTelemetryHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $days = (int) AlertThreshold::get('telemetry_history_retention_days', 90);

        $deleted = IotDeviceTelemetryHistory::where('recorded_at', '<', now()->subDays($days))->delete();

        Log::info('Telemetry history pruned.', ['rows_deleted' => $deleted, 'retention_days' => $days]);
    }
}
